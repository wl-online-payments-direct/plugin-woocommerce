<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Exception;
use Worldline\Assets\Asset;
use Worldline\Assets\AssetManager;
use Worldline\Assets\Script;
use Worldline\Assets\Style;
use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ExtendingModule;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentGateway;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Config\CaptureMode;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin\StatusUpdateAction;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Cron\AutoCaptureHandler;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Notice\OrderActionNotice;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Validator\CurrencySupportValidator;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Worldline\Vendor\Psr\Container\ContainerExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Container\NotFoundExceptionInterface;
use Worldline\Vendor\Psr\Log\LoggerInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Throwable;
use WC_Order;
use WC_Order_Refund;
use Worldline\Vendor\WP_CLI;
class WorldlinePaymentGatewayModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public const PACKAGE_NAME = 'worldline-payment-gateway';
    public const SESSION_TIMEOUT_SWEEP_INTERVAL = 30 * \MINUTE_IN_SECONDS;
    public const SESSION_TIMEOUT_SWEEP_BATCH_SIZE = 50;
    /**
     * Query var carrying the sweep's meta clauses on the legacy posts store.
     * Deliberately distinct from the one AutoCaptureHandler uses - both register
     * on the same filter and can run in a single Action Scheduler batch.
     */
    protected const SWEEP_META_QUERY_VAR = 'wlop_session_timeout_meta_query';
    /**
     * @throws Exception
     */
    public function run(ContainerInterface $container) : bool
    {
        $this->registerOrderActions($container);
        $this->registerAdminNotices($container);
        $this->registerCurrencyValidator($container);
        $this->registerCliCommands($container);
        $this->registerRefundSaving($container);
        $this->registerCheckoutCompletionHandler($container);
        $this->scheduleAutoCapturing($container);
        $this->schedulePendingOrderCleanup($container);
        $this->registerAdminOrderDetails($container);
        return \true;
    }
    public function services() : array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
        }
        return $services();
    }
    /**
     * @inheritDoc
     */
    public function extensions() : array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = (require_once \dirname(__DIR__) . '/inc/extensions.php');
        }
        return $extensions();
    }
    protected function registerOrderActions(ContainerInterface $container) : void
    {
        \add_filter(
            'woocommerce_order_actions',
            /**
             * @throws NotFoundExceptionInterface
             * @throws ContainerExceptionInterface
             */
            static function (array $orderActions, WC_Order $wcOrder) use($container) : array {
                $wlopOrderActions = [$container->get('worldline_payment_gateway.admin.render_capture_action'), $container->get('worldline_payment_gateway.admin.status_update_action')];
                foreach ($wlopOrderActions as $wlopOrderAction) {
                    $orderActions = $wlopOrderAction->render($orderActions, $wcOrder);
                }
                return $orderActions;
            },
            10,
            2
        );
        \add_action('woocommerce_order_action_worldline_capture_order', static function (WC_Order $wcOrder) use($container) {
            $authorizedPaymentProcessor = $container->get('worldline_payment_gateway.authorized_payment_processor');
            \assert($authorizedPaymentProcessor instanceof AuthorizedPaymentProcessor);
            $authorizedPaymentProcessor->captureAuthorizedPayment($wcOrder);
        });
        \add_action('woocommerce_order_action_worldline_update_order_status', static function (WC_Order $wcOrder) use($container) {
            $action = $container->get('worldline_payment_gateway.admin.status_update_action');
            \assert($action instanceof StatusUpdateAction);
            $action->execute($wcOrder);
        });
    }
    protected function registerCliCommands(ContainerInterface $container) : void
    {
        if (\defined('Worldline\\Vendor\\WP_CLI') && WP_CLI) {
            try {
                /** @psalm-suppress MixedArgument */
                WP_CLI::add_command('wlop order', $container->get('worldline_payment_gateway.cli.status_update_command'));
            } catch (Exception $exception) {
            }
        }
    }
    protected function registerAdminNotices(ContainerInterface $container) : void
    {
        \add_action('admin_notices', static function () use($container) {
            $orderActionNotice = $container->get('worldline_payment_gateway.order_action_notice');
            \assert($orderActionNotice instanceof OrderActionNotice);
            $message = $orderActionNotice->message();
            if (!\is_null($message)) {
                echo \wp_kses($message, ['p' => [], 'div' => ['class' => \true]]);
            }
        });
        /**
         * Show a notice in the admin when store currency is not supported by Worldline
         */
        \add_action('admin_notices', static function () use($container) : void {
            $wlopGateway = $container->get('worldline_payment_gateway.gateway');
            \assert($wlopGateway instanceof PaymentGateway);
            if ($wlopGateway->enabled !== 'yes') {
                return;
            }
            $currencySupportValidator = $container->get('worldline_payment_gateway.currency_support_validator');
            \assert($currencySupportValidator instanceof CurrencySupportValidator);
            if ($currencySupportValidator->wlopSupportStoreCurrency()) {
                return;
            }
            $message = \__('The currency currently used by your store is not enabled in your Worldline account.', 'worldline-for-woocommerce');
            $alert = "<div class='notice notice-error is-dismissible'>\n                            <p>" . $message . "</p>\n                        </div>";
            echo \wp_kses($alert, ['p' => [], 'div' => ['class' => \true]]);
        });
    }
    protected function registerCurrencyValidator(ContainerInterface $container) : void
    {
        \add_action('woocommerce_settings_saved', static function () use($container) {
            $currencySupportValidator = $container->get('worldline_payment_gateway.currency_support_validator');
            \assert($currencySupportValidator instanceof CurrencySupportValidator);
            $currencySupportValidator->updateWlopStoreCurrencySupport();
        });
    }
    protected function registerRefundSaving(ContainerInterface $container) : void
    {
        \add_action('woocommerce_create_refund', static function (WC_Order_Refund $refund, array $args) use($container) : void {
            if (!$args['refund_payment']) {
                return;
            }
            $wcOrder = \wc_get_order($args['order_id']);
            if (!$wcOrder instanceof WC_Order) {
                return;
            }
            if ($wcOrder->get_payment_method() !== GatewayIds::HOSTED_CHECKOUT) {
                return;
            }
            $refundData = \array_merge($args, ['wlop_created_time' => \time()]);
            $wcOrder->add_meta_data(OrderMetaKeys::PENDING_REFUNDS, $refundData);
            $wcOrder->save();
        }, 10, 2);
    }
    public function registerCheckoutCompletionHandler(ContainerInterface $container) : void
    {
        \add_filter('query_vars', static function (array $publicQueryVars) : array {
            $publicQueryVars[] = 'hostedCheckoutId';
            return $publicQueryVars;
        });
        \add_action('wlop_order_received_page', static function (WC_Order $wcOrder) use($container) : void {
            if (!\in_array($wcOrder->get_payment_method(), GatewayIds::HOSTED_CHECKOUT_GATEWAYS, \true)) {
                return;
            }
            $hostedCheckoutId = (string) \get_query_var('hostedCheckoutId');
            $apiClient = $container->get('worldline_payment_gateway.api.client');
            \assert($apiClient instanceof MerchantClientInterface);
            if (!$hostedCheckoutId) {
                throw new Exception("Unable to retrieve hostedCheckoutId for the order {$wcOrder->get_id()}");
            }
            $hostedCheckout = $apiClient->hostedCheckout()->getHostedCheckout($hostedCheckoutId);
            $payment = $hostedCheckout->getCreatedPaymentOutput()->getPayment();
            $paymentOutput = $payment->getPaymentOutput();
            $refs = $paymentOutput->getReferences();
            $merchantReference = (int) $refs->getMerchantReference();
            if ($merchantReference !== $wcOrder->get_id()) {
                throw new Exception("Unexpected merchantReference {$refs->getMerchantReference()}");
            }
            $transactionId = $payment->getId();
            $wlopWcOrder = new WlopWcOrder($wcOrder);
            $wlopWcOrder->setTransactionId($transactionId);
            $orderUpdater = $container->get('worldline_payment_gateway.order_updater');
            \assert($orderUpdater instanceof OrderUpdater);
            /*
             * Wait for a concurrent writer rather than skipping. This runs on
             * the order-received page, which Worldline hits at the same moment
             * it fires the webhook - and this side holds the better data, since
             * it fetched the very checkout the shopper just came back from.
             */
            $orderUpdater->updateFromResponse($wlopWcOrder, $payment, OrderUpdater::INTERACTIVE_LOCK_WAIT_SECONDS);
        });
    }
    protected function scheduleAutoCapturing(ContainerInterface $container) : void
    {
        $hook = 'wlop_auto_capturing';
        \add_action('action_scheduler_init', static function () use($container, $hook) : void {
            if (\as_has_scheduled_action($hook)) {
                return;
            }
            $captureMode = $container->get('config.capture_mode');
            if ($captureMode === CaptureMode::MANUAL) {
                return;
            }
            $authorizationMode = $container->get('config.authorization_mode');
            if ($authorizationMode === AuthorizationMode::SALE) {
                return;
            }
            $interval = (int) $container->get('worldline_payment_gateway.auto_capture.handler.interval');
            \as_schedule_single_action(\time() + $interval, $hook, [], 'wlop', \true);
        });
        \add_action($hook, static function () use($container) : void {
            $captureMode = $container->get('config.capture_mode');
            if ($captureMode === CaptureMode::MANUAL) {
                return;
            }
            $authorizationMode = $container->get('config.authorization_mode');
            if ($authorizationMode === AuthorizationMode::SALE) {
                return;
            }
            $handler = $container->get('worldline_payment_gateway.auto_capture.handler');
            \assert($handler instanceof AutoCaptureHandler);
            $handler->execute();
        });
    }
    protected function schedulePendingOrderCleanup(ContainerInterface $container) : void
    {
        $hook = 'wlop_cleanup_pending_orders';
        \add_action('action_scheduler_init', static function () use($hook) : void {
            $group = 'wlop';
            if (\as_has_scheduled_action($hook, [], $group)) {
                return;
            }
            $startTime = \time() + self::SESSION_TIMEOUT_SWEEP_INTERVAL;
            \as_schedule_recurring_action($startTime, self::SESSION_TIMEOUT_SWEEP_INTERVAL, $hook, [], $group, \true);
        });
        \add_action($hook, static function () use($container) : void {
            self::failTimedOutPendingOrders($container);
        });
    }
    /**
     * Session-timeout enforcement: fail pending Worldline orders whose hosted
     * checkout session has expired (creation time + configured timeout elapsed),
     * so that WooCommerce releases the reserved stock back into inventory.
     *
     * The timeout is measured from the moment the customer clicked "Pay", which
     * is recorded as the `_wlop_creation_time` meta. Querying by that meta also
     * naturally scopes the sweep to Worldline orders only.
     */
    /**
     * Meta clauses selecting orders the sweep may act on.
     *
     * Two conditions, both required. The creation time is older than the
     * configured window, and Worldline has **never reported a status** for the
     * order: `OrderInitTrait` seeds the status-code meta with -1 when the shopper
     * clicks Pay, and the first status received overwrites it, so a value below
     * zero means no payment object was ever created. That is exactly the case
     * where no webhook can arrive and the sweep is the only cleanup.
     *
     * Without the second clause the sweep also hits orders the platform reports
     * as legitimately pending - codes 4, 46 and 51 all map to `pending`, which is
     * normal and long-lived for bank transfer, SEPA direct debit, mealvouchers and
     * CVCO. Filtering by gateway instead would not help: all four sit in
     * HOSTED_CHECKOUT_GATEWAYS.
     *
     * @return array<int, array<string, mixed>>
     */
    protected static function sweepMetaQuery(int $thresholdTs) : array
    {
        return [['key' => OrderMetaKeys::CREATION_TIME, 'value' => $thresholdTs, 'compare' => '<', 'type' => 'NUMERIC'], ['key' => OrderMetaKeys::TRANSACTION_STATUS_CODE, 'value' => 0, 'compare' => '<', 'type' => 'NUMERIC']];
    }
    protected static function failTimedOutPendingOrders(ContainerInterface $container) : void
    {
        $sessionTimeoutMinutes = (int) $container->get('config.session_timeout_minutes');
        $thresholdTs = \time() - $sessionTimeoutMinutes * \MINUTE_IN_SECONDS;
        $query = ['status' => 'pending', 'payment_method' => GatewayIds::ALL, 'limit' => self::SESSION_TIMEOUT_SWEEP_BATCH_SIZE, 'orderby' => 'date', 'order' => 'ASC', 'return' => 'ids'];
        $metaQuery = self::sweepMetaQuery($thresholdTs);
        $legacyMetaQueryFilter = null;
        if (OrderUtil::custom_orders_table_usage_is_enabled()) {
            $query['meta_query'] = $metaQuery;
        } else {
            $query[self::SWEEP_META_QUERY_VAR] = $metaQuery;
            /**
             * @param array $query - Args for WP_Query.
             * @param array $queryVars - Query vars from WC_Order_Query.
             * @return array modified $query
             * @psalm-suppress MixedArgument, MixedArrayAccess, MixedAssignment
             */
            $legacyMetaQueryFilter = static function ($query, $queryVars) {
                if (!empty($queryVars[self::SWEEP_META_QUERY_VAR])) {
                    $query['meta_query'] = \array_merge($query['meta_query'] ?? [], $queryVars[self::SWEEP_META_QUERY_VAR]);
                }
                return $query;
            };
            \add_filter('woocommerce_order_data_store_cpt_get_orders_query', $legacyMetaQueryFilter, 10, 2);
        }
        try {
            $order_ids = \wc_get_orders($query);
        } finally {
            if ($legacyMetaQueryFilter !== null) {
                \remove_filter('woocommerce_order_data_store_cpt_get_orders_query', $legacyMetaQueryFilter, 10);
            }
        }
        $failed = self::failOrders(\is_array($order_ids) ? $order_ids : [], $sessionTimeoutMinutes);
        self::logSweepOutcome($container, \is_array($order_ids) ? \count($order_ids) : 0, $failed, $sessionTimeoutMinutes);
    }
    /**
     * Transitions the given pending orders to `failed` and releases their held stock.
     *
     * @param array<int|string|WC_Order> $orderIds
     * @return int How many orders were actually transitioned.
     */
    protected static function failOrders(array $orderIds, int $sessionTimeoutMinutes) : int
    {
        if (empty($orderIds)) {
            return 0;
        }
        \add_filter('woocommerce_email_enabled_failed_order', '__return_false', 99);
        \add_filter('woocommerce_email_enabled_customer_failed_order', '__return_false', 99);
        $failed = 0;
        try {
            foreach ($orderIds as $orderId) {
                $failed += self::failTimedOutOrder($orderId, $sessionTimeoutMinutes);
            }
        } finally {
            \remove_filter('woocommerce_email_enabled_failed_order', '__return_false', 99);
            \remove_filter('woocommerce_email_enabled_customer_failed_order', '__return_false', 99);
        }
        return $failed;
    }
    /**
     * @param int|string|WC_Order $orderId
     * @return int 1 when the order was transitioned, 0 otherwise.
     */
    protected static function failTimedOutOrder($orderId, int $sessionTimeoutMinutes) : int
    {
        try {
            $order = \wc_get_order($orderId);
            if (!$order instanceof WC_Order || $order->get_status() !== 'pending') {
                return 0;
            }
            $order->update_status('failed', \sprintf('Session timed out after %d minute(s) without a completed payment (Worldline plugin).', $sessionTimeoutMinutes));
            if (\function_exists('wc_release_stock_for_order')) {
                \wc_release_stock_for_order($order);
            }
            return 1;
        } catch (Throwable $exception) {
            \do_action('wlop.session_timeout_sweep_error', ['wcOrderId' => $orderId, 'exception' => $exception]);
            return 0;
        }
    }
    /**
     * Reports what the sweep did, so a truncated run is not silent.
     *
     * A full batch means more expired orders are still queued: they stay pending
     * with their stock reserved until a later run, and without this line nothing
     * explains the delay. The debug line additionally confirms the sweep ran at
     * all, which is otherwise unverifiable on a low-traffic site where WP-Cron
     * fires irregularly.
     */
    protected static function logSweepOutcome(ContainerInterface $container, int $found, int $failed, int $sessionTimeoutMinutes) : void
    {
        $logger = $container->get('worldline_logger.logger');
        \assert($logger instanceof LoggerInterface);
        if ($found >= self::SESSION_TIMEOUT_SWEEP_BATCH_SIZE) {
            $logger->warning(\sprintf('Session timeout sweep processed a full batch of %1$d orders. More expired ' . 'pending orders are still waiting and will be handled by one of the next ' . 'runs, %2$d minutes apart.', self::SESSION_TIMEOUT_SWEEP_BATCH_SIZE, (int) (self::SESSION_TIMEOUT_SWEEP_INTERVAL / \MINUTE_IN_SECONDS)));
        }
        $logger->debug(\sprintf('Session timeout sweep failed %1$d of %2$d candidate order(s) older than %3$d minute(s).', $failed, $found, $sessionTimeoutMinutes));
    }
    protected function registerAdminOrderDetails(ContainerInterface $container) : void
    {
        \add_action(AssetManager::ACTION_SETUP, static function (AssetManager $assetManager) use($container) {
            /** @var callable(string,string):string $getModuleAssetUrl */
            $getModuleAssetUrl = $container->get('assets.get_module_asset_url');
            $assetManager->register(new Script("worldline-" . self::PACKAGE_NAME, $getModuleAssetUrl(self::PACKAGE_NAME, 'backend-main.js'), Asset::BACKEND), new Style("worldline-" . self::PACKAGE_NAME, $getModuleAssetUrl(self::PACKAGE_NAME, 'backend-main.css'), Asset::BACKEND));
        });
        \add_action('add_meta_boxes', function (string $post_type, $post = null) {
            if ($post_type !== 'shop_order') {
                return;
            }
            if (!$post) {
                return;
            }
            $wcOrder = \wc_get_order($post->ID);
            if (!\in_array($wcOrder->get_payment_method(), GatewayIds::ALL, \true)) {
                return;
            }
            \add_meta_box('worldline_payment_info', 'Worldline Online Payments', function () use($wcOrder) {
                $this->render_worldline_meta_box($wcOrder);
            }, 'shop_order', 'normal', 'high');
        }, 10, 2);
        \add_action('add_meta_boxes_woocommerce_page_wc-orders', function ($wcOrder) {
            if (!$wcOrder instanceof WC_Order) {
                return;
            }
            if (!\in_array($wcOrder->get_payment_method(), GatewayIds::ALL, \true)) {
                return;
            }
            \add_meta_box('worldline_payment_info', 'Worldline Online Payments', [$this, 'render_worldline_meta_box'], \wc_get_page_screen_id('shop-order'), 'normal', 'high');
        });
    }
    public function render_worldline_meta_box(WC_Order $wcOrder) : void
    {
        $order = new WlopWcOrder($wcOrder);
        if ($order->statusCode() === -1) {
            echo '<div class="wl-wrapper">Pending payment</div>';
            return;
        }
        $payments = $order->payments();
        if ($payments === []) {
            echo '<div class="wl-wrapper">Pending payment</div>';
            return;
        }
        $showHeaders = \count($payments) > 1;
        echo '<div class="wl-meta">';
        foreach ($payments as $entry) {
            $this->renderPaymentSection($entry, $showHeaders);
        }
        echo '</div>';
    }
    /**
     * @param array<string, mixed> $entry
     */
    private function renderPaymentSection(array $entry, bool $showHeader) : void
    {
        $methodName = (string) ($entry['methodName'] ?? '');
        $statusCode = $entry['statusCode'] ?? null;
        $status = (string) ($entry['status'] ?? '');
        $paymentId = (string) ($entry['paymentId'] ?? '');
        $card = $this->formatCard($entry['card'] ?? []);
        $mandateRef = (string) ($entry['sepaMandateReference'] ?? '');
        $threeDs = \is_array($entry['threeDS'] ?? null) ? $entry['threeDS'] : [];
        echo '<section class="wl-payment">';
        if ($showHeader) {
            echo '<header class="wl-payment-head">';
            echo '<span class="wl-payment-title">' . \esc_html($methodName !== '' ? $methodName : \__('Payment', 'worldline-for-woocommerce')) . '</span>';
            echo '<span class="wl-payment-amount">' . \wp_kses_post($this->formatAmount($entry)) . '</span>';
            echo '</header>';
        }
        echo '<div class="wl-wrapper">';
        echo '<div class="wl-col">';
        echo '<h4>Payment information</h4>';
        echo '<div class="wl-row"><span class="wl-label">Payment method</span><span class="wl-val">Worldline' . ($methodName !== '' ? ' [' . \esc_html($methodName) . ']' : '') . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Status</span><span class="wl-val">' . \esc_html($status) . ($statusCode !== null ? ' (' . \esc_html((string) $statusCode) . ')' : '') . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Payment ID</span><span class="wl-val">' . \esc_html($paymentId) . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Amount</span><span class="wl-val">' . \wp_kses_post($this->formatAmount($entry)) . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Card</span><span class="wl-val">' . \esc_html($card) . '</span></div>';
        if ($mandateRef !== '') {
            echo '<div class="wl-row"><span class="wl-label">Mandate reference</span><span class="wl-val">' . \esc_html($mandateRef) . '</span></div>';
        }
        echo '</div>';
        echo '<div class="wl-col">';
        echo '<h4>Fraud information</h4>';
        echo '<div class="wl-row"><span class="wl-label">Fraud result</span><span class="wl-val">' . \esc_html(\ucfirst((string) ($entry['fraudResult'] ?? ''))) . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">3DS Liability</span><span class="wl-val">' . \esc_html(\ucfirst((string) ($threeDs['liability'] ?? ''))) . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Exemption</span><span class="wl-val">' . \esc_html(\ucfirst((string) ($threeDs['appliedExemption'] ?? ''))) . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Authentication</span><span class="wl-val">' . \esc_html((string) ($threeDs['authenticationStatus'] ?? '')) . '</span></div>';
        echo '</div>';
        echo '</div>';
        echo '</section>';
    }
    /**
     * @param array<string, mixed> $entry
     */
    private function formatAmount(array $entry) : string
    {
        $cents = (int) ($entry['amountCents'] ?? 0);
        $currency = (string) ($entry['currency'] ?? '');
        if ($currency === '') {
            return '';
        }
        $converter = new MoneyAmountConverter();
        $decimal = $converter->centValueToDecimalValue($cents, $currency);
        return (string) \wc_price($decimal, ['currency' => $currency, 'thousand_separator' => '']);
    }
    /**
     * @param mixed $card
     */
    private function formatCard($card) : string
    {
        if (!\is_array($card)) {
            return '';
        }
        $bin = (string) ($card['bin'] ?? '');
        $number = (string) ($card['number'] ?? '');
        if ($bin === '') {
            return $number;
        }
        if (\substr($number, 0, \strlen($bin)) === $bin) {
            return $number;
        }
        return \substr_replace($number, $bin, 0, \strlen($bin));
    }
}
