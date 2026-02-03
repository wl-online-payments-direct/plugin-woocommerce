<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

use Automattic\WooCommerce\Utilities\OrderUtil;
use Exception;
use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\CancellationIntervals;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\CaptureMode;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin\StatusUpdateAction;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Cron\AutoCaptureHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Notice\OrderActionNotice;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Validator\CurrencySupportValidator;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Container\NotFoundExceptionInterface;
use WC_Order;
use WC_Order_Refund;
use Syde\Vendor\Worldline\WP_CLI;
class WorldlinePaymentGatewayModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public const PACKAGE_NAME = 'worldline-payment-gateway';
    public const CANCELLATION_RECURRENCE_INTERVAL = 30 * \MINUTE_IN_SECONDS;
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
        if (\defined('Syde\\Vendor\\Worldline\\WP_CLI') && WP_CLI) {
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
            $savedTransactionId = $wlopWcOrder->transactionId();
            if (empty($savedTransactionId)) {
                $wlopWcOrder->setTransactionId($transactionId);
            }
            $orderUpdater = $container->get('worldline_payment_gateway.order_updater');
            \assert($orderUpdater instanceof OrderUpdater);
            $orderUpdater->updateFromResponse($wlopWcOrder, $payment);
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
        \add_action('action_scheduler_init', static function () use($container, $hook) : void {
            $group = 'wlop';
            $cancellationHours = $container->get('config.automatic_cancellation_hours');
            if ($cancellationHours === CancellationIntervals::DISABLED) {
                \as_unschedule_all_actions($hook, [], $group);
                return;
            }
            if (\as_has_scheduled_action($hook, [], $group)) {
                return;
            }
            $startTime = \time() + self::CANCELLATION_RECURRENCE_INTERVAL;
            \as_schedule_recurring_action($startTime, self::CANCELLATION_RECURRENCE_INTERVAL, $hook, [], $group);
        });
        \add_action($hook, static function () use($container) : void {
            $cancellationHours = $container->get('config.automatic_cancellation_hours');
            if ($cancellationHours === CancellationIntervals::DISABLED) {
                return;
            }
            $threshold = new \DateTimeImmutable("-{$cancellationHours} hours", new \DateTimeZone('UTC'));
            $args = ['status' => 'pending', 'limit' => -1, 'orderby' => 'date', 'order' => 'ASC', 'date_before' => $threshold->format('Y-m-d H:i:s'), 'return' => 'ids'];
            $order_ids = \wc_get_orders($args);
            if (empty($order_ids)) {
                return;
            }
            foreach ($order_ids as $order_id) {
                $order = \wc_get_order($order_id);
                if (!$order instanceof WC_Order || $order->get_status() !== 'pending') {
                    continue;
                }
                $order->update_status('cancelled', 'Automatically cancelled after ' . $cancellationHours . ' hours (Worldline plugin).');
            }
        });
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
        echo '<div class="wl-wrapper">';
        // Left Column: Payment Information
        echo '<div class="wl-col">';
        echo '<h4>Payment information</h4>';
        echo '<div class="wl-row"><span class="wl-label">Payment method</span><span class="wl-val">Worldline' . ($order->paymentMethodName() ? ' [' . $order->paymentMethodName() . ']' : '') . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Status</span><span class="wl-val"><span class="wl-val">' . $order->status() . ' (' . $order->statusCode() . ')</span></span></div>';
        echo '<div class="wl-row"><span class="wl-label">Payment ID</span><span class="wl-val">' . $order->transactionId() . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Amount</span><span class="wl-val">' . $order->amount() . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Card</span><span class="wl-val">' . $order->creditCard() . '</span></div>';
        echo '</div>';
        // Right Column: Fraud Information
        echo '<div class="wl-col">';
        echo '<h4>Fraud information</h4>';
        echo '<div class="wl-row"><span class="wl-label">Fraud result</span><span class="wl-val">' . $order->fraudResult() . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">3DS Liability</span><span class="wl-val">' . $order->threeDSecureLiability() . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Exemption</span><span class="wl-val">' . $order->threeDSecureExemption() . '</span></div>';
        echo '<div class="wl-row"><span class="wl-label">Authentication</span><span class="wl-val">' . $order->threeDSecureAuthStatus() . '</span></div>';
        echo '</div>';
        echo '</div>';
    }
}
