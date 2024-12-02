<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

use Exception;
use Syde\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin\StatusUpdateAction;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Notice\OrderActionNotice;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Refund\RefundProcessor;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Validator\CurrencySupportValidator;
use Syde\Vendor\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Psr\Container\ContainerInterface;
use Syde\Vendor\Psr\Container\NotFoundExceptionInterface;
use WC_Order;
use WC_Order_Refund;
use Syde\Vendor\WP_CLI;
class WorldlinePaymentGatewayModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    /**
     * @throws Exception
     */
    public function run(ContainerInterface $container): bool
    {
        $this->registerOrderActions($container);
        $this->registerAdminNotices($container);
        $this->registerCurrencyValidator($container);
        $this->registerCliCommands($container);
        $this->registerRefundSaving($container);
        return \true;
    }
    public function services(): array
    {
        static $services;
        if ($services === null) {
            $services = require_once dirname(__DIR__) . '/inc/services.php';
        }
        return $services();
    }
    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = require_once dirname(__DIR__) . '/inc/extensions.php';
        }
        return $extensions();
    }
    protected function registerOrderActions(ContainerInterface $container): void
    {
        add_filter(
            'woocommerce_order_actions',
            /**
             * @throws NotFoundExceptionInterface
             * @throws ContainerExceptionInterface
             */
            static function (array $orderActions, WC_Order $wcOrder) use ($container): array {
                $wlopOrderActions = [$container->get('worldline_payment_gateway.admin.render_capture_action'), $container->get('worldline_payment_gateway.admin.status_update_action')];
                foreach ($wlopOrderActions as $wlopOrderAction) {
                    $orderActions = $wlopOrderAction->render($orderActions, $wcOrder);
                }
                return $orderActions;
            },
            10,
            2
        );
        add_action('woocommerce_order_action_worldline_capture_order', static function (WC_Order $wcOrder) use ($container) {
            $authorizedPaymentProcessor = $container->get('worldline_payment_gateway.authorized_payment_processor');
            assert($authorizedPaymentProcessor instanceof AuthorizedPaymentProcessor);
            $authorizedPaymentProcessor->captureAuthorizedPayment($wcOrder);
        });
        add_action('woocommerce_order_action_worldline_update_order_status', static function (WC_Order $wcOrder) use ($container) {
            $action = $container->get('worldline_payment_gateway.admin.status_update_action');
            assert($action instanceof StatusUpdateAction);
            $action->execute($wcOrder);
        });
    }
    protected function registerCliCommands(ContainerInterface $container): void
    {
        if (defined('Syde\Vendor\WP_CLI') && WP_CLI) {
            try {
                /** @psalm-suppress MixedArgument */
                WP_CLI::add_command('wlop order', $container->get('worldline_payment_gateway.cli.status_update_command'));
            } catch (Exception $exception) {
            }
        }
    }
    protected function registerAdminNotices(ContainerInterface $container): void
    {
        add_action('admin_notices', static function () use ($container) {
            $orderActionNotice = $container->get('worldline_payment_gateway.order_action_notice');
            assert($orderActionNotice instanceof OrderActionNotice);
            $message = $orderActionNotice->message();
            if (!is_null($message)) {
                echo wp_kses($message, ['p' => [], 'div' => ['class' => \true]]);
            }
        });
        /**
         * Show a notice in the admin when store currency is not supported by Worldline
         */
        add_action('admin_notices', static function () use ($container): void {
            $wlopGateway = $container->get('worldline_payment_gateway.gateway');
            assert($wlopGateway instanceof PaymentGateway);
            if ($wlopGateway->enabled !== 'yes') {
                return;
            }
            $currencySupportValidator = $container->get('worldline_payment_gateway.currency_support_validator');
            assert($currencySupportValidator instanceof CurrencySupportValidator);
            if ($currencySupportValidator->wlopSupportStoreCurrency()) {
                return;
            }
            $message = __('The currency currently used by your store is not enabled in your Worldline account.', 'worldline-for-woocommerce');
            $alert = "<div class='notice notice-error is-dismissible'>\n                            <p>" . $message . "</p>\n                        </div>";
            echo wp_kses($alert, ['p' => [], 'div' => ['class' => \true]]);
        });
    }
    protected function registerCurrencyValidator(ContainerInterface $container): void
    {
        add_action('woocommerce_settings_saved', static function () use ($container) {
            $currencySupportValidator = $container->get('worldline_payment_gateway.currency_support_validator');
            assert($currencySupportValidator instanceof CurrencySupportValidator);
            $currencySupportValidator->updateWlopStoreCurrencySupport();
        });
    }
    protected function registerRefundSaving(ContainerInterface $container): void
    {
        add_action('woocommerce_create_refund', static function (WC_Order_Refund $refund, array $args) use ($container): void {
            if (!$args['refund_payment']) {
                return;
            }
            $wcOrder = wc_get_order($args['order_id']);
            if (!$wcOrder instanceof WC_Order) {
                return;
            }
            if ($wcOrder->get_payment_method() !== $container->get('worldline_payment_gateway.id')) {
                return;
            }
            $refundData = array_merge($args, ['wlop_created_time' => time()]);
            $wcOrder->add_meta_data(OrderMetaKeys::PENDING_REFUNDS, $refundData);
            $wcOrder->save();
        }, 10, 2);
    }
}
