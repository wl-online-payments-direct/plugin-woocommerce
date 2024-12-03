<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Config;

use Syde\Vendor\Dhii\Validation\Exception\ValidationFailedExceptionInterface;
use Syde\Vendor\Dhii\Validator\CallbackValidator;
use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Psr\Container\ContainerInterface;
use Syde\Vendor\Psr\Container\NotFoundExceptionInterface;
class ConfigModule implements ExecutableModule, ServiceModule
{
    use ModuleClassNameIdTrait;
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ContainerInterface $container): bool
    {
        add_action(AssetManager::ACTION_SETUP, static function (AssetManager $assetManager) use ($container) {
            $moduleDirName = 'worldline-config';
            $assetsBaseUrl = $container->get('assets.module_url')($moduleDirName);
            $assetManager->register(new Script("worldline-{$moduleDirName}", "{$assetsBaseUrl}/backend-main.js", Asset::BACKEND), new Style("worldline-{$moduleDirName}", "{$assetsBaseUrl}/backend-main.css", Asset::BACKEND));
        });
        add_filter('woocommerce_settings_api_sanitized_fields_' . $container->get('worldline_payment_gateway.id'), static function (array $settings) use ($container): array {
            $connectionValidator = $container->get('config.connection_validator');
            assert($connectionValidator instanceof CallbackValidator);
            try {
                $connectionValidator->validate($settings);
            } catch (ValidationFailedExceptionInterface $exc) {
                $gateway = $container->get('worldline_payment_gateway.gateway');
                assert($gateway instanceof PaymentGateway);
                $gateway->add_error(__('Connection to the Worldline API failed. Check the credentials.', 'worldline-for-woocommerce'));
            }
            return $settings;
        });
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
}
