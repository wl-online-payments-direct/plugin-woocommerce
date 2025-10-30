<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config;

use Syde\Vendor\Worldline\Dhii\Validation\Exception\ValidationFailedExceptionInterface;
use Syde\Vendor\Worldline\Dhii\Validator\CallbackValidator;
use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Container\NotFoundExceptionInterface;
class ConfigModule implements ExecutableModule, ServiceModule
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
            $moduleName = 'config';
            /** @var callable(string,string):string $getModuleAssetUrl */
            $getModuleAssetUrl = $container->get('assets.get_module_asset_url');
            $assetManager->register(new Script("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'backend-main.js'), Asset::BACKEND), new Style("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'backend-main.css'), Asset::BACKEND));
        });
        \add_filter('woocommerce_settings_api_sanitized_fields_' . GatewayIds::HOSTED_CHECKOUT, static function (array $settings) use($container) : array {
            $connectionValidator = $container->get('config.connection_validator');
            \assert($connectionValidator instanceof CallbackValidator);
            try {
                $connectionValidator->validate($settings);
            } catch (ValidationFailedExceptionInterface $exc) {
                $gateway = $container->get('worldline_payment_gateway.gateway');
                \assert($gateway instanceof PaymentGateway);
                $gateway->add_error(\__('Connection to the Worldline API failed. Check the credentials.', 'worldline-for-woocommerce'));
                $old = \get_option($gateway->get_option_key(), $gateway->settings ?? []);
                return \is_array($old) ? $old : [];
            }
            return $settings;
        });
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
}
