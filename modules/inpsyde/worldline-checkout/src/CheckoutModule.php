<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Checkout;

use Exception;
use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Container\NotFoundExceptionInterface;
use WC_Order;
class CheckoutModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action(AssetManager::ACTION_SETUP, static function (AssetManager $assetManager) use($container) {
            $moduleName = 'checkout';
            /** @var callable(string,string):string $getModuleAssetUrl */
            $getModuleAssetUrl = $container->get('assets.get_module_asset_url');
            $assetManager->register(new Script("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'frontend-main.js'), Asset::FRONTEND), new Style("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'frontend-main.css'), Asset::FRONTEND));
        });
        $this->registerScheduledStatusUpdateHandler($container);
        $this->registerCheckoutCompletionHandling($container);
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
    protected function registerCheckoutCompletionHandling(ContainerInterface $container) : void
    {
        \add_action('wp', function () use($container) : void {
            if (!\is_order_received_page()) {
                return;
            }
            $wcOrder = null;
            // phpcs:disable WordPress.Security.NonceVerification
            if (isset($_GET['key']) && \is_string($_GET['key'])) {
                $wcOrderKey = \sanitize_text_field(\wp_unslash($_GET['key']));
                // phpcs:enable WordPress.Security.NonceVerification
                $wcOrderId = \wc_get_order_id_by_order_key($wcOrderKey);
                $wcOrder = \wc_get_order($wcOrderId);
            }
            if (!$wcOrder instanceof WC_Order) {
                return;
            }
            if (!\in_array($wcOrder->get_payment_method(), GatewayIds::ALL, \true)) {
                return;
            }
            $this->scheduledStatusUpdate($wcOrder->get_id());
            /**
             * Fires immediately after an order is confirmed on the order received page.
             *
             * @param WC_Order $wcOrder The order object.
             */
            \do_action('wlop_order_received_page', $wcOrder);
        }, 5);
    }
    private function scheduledStatusUpdate(int $wcOrderId) : void
    {
        $delay = (int) \apply_filters('wlop_status_update_interval', 5 * \MINUTE_IN_SECONDS);
        \as_schedule_single_action(\time() + $delay, 'wlop_update_status', ['wcOrderId' => $wcOrderId]);
    }
    private function registerScheduledStatusUpdateHandler(ContainerInterface $container) : void
    {
        \add_action('wlop_update_status', function (int $wcOrderId) use($container) : void {
            $wcOrder = \wc_get_order($wcOrderId);
            if (!$wcOrder instanceof WC_Order) {
                return;
            }
            if ($wcOrder->get_status() !== 'pending') {
                return;
            }
            $orderUpdater = $container->get('worldline_payment_gateway.order_updater');
            \assert($orderUpdater instanceof OrderUpdater);
            $orderUpdater->update(new WlopWcOrder($wcOrder));
            if ($wcOrder->get_status() === 'pending') {
                $this->scheduledStatusUpdate($wcOrderId);
            }
        });
    }
}
