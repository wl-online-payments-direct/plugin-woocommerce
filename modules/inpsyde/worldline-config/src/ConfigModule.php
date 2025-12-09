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
    public const LOGO_URL_OPTION = 'logo_url';
    public const DEFAULT_LOGO_RELATIVE_PATH = 'modules/inpsyde/worldline-payment-gateway/assets/images/worldline-logo.svg';
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ContainerInterface $container) : bool
    {
        $self = $this;
        \add_action(AssetManager::ACTION_SETUP, static function (AssetManager $assetManager) use($container) {
            $moduleName = 'config';
            /** @var callable(string,string):string $getModuleAssetUrl */
            $getModuleAssetUrl = $container->get('assets.get_module_asset_url');
            $assetManager->register(new Script("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'backend-main.js'), Asset::BACKEND), new Style("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'backend-main.css'), Asset::BACKEND));
        });
        \add_filter('woocommerce_settings_api_sanitized_fields_' . GatewayIds::HOSTED_CHECKOUT, static function (array $settings) use($container, $self) : array {
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
            $self->handleLogoOnSave($settings);
            return $settings;
        });
        \add_action('wp_ajax_wlop_hosted_tokenization_config', function () use($container) {
            $this->handleConfigAjax($container);
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
    /**
     * Handles the AJAX requests for the plugin settings.
     *
     * @param ContainerInterface $container
     *
     * @return void
     */
    protected function handleConfigAjax(ContainerInterface $container) : void
    {
        if (isset($_FILES['logo_file']) && $_FILES['logo_file']['error'] === \UPLOAD_ERR_OK) {
            $this->handleLogoUpload($_FILES['logo_file']);
            return;
        }
        $logoUrl = (string) \get_option(self::LOGO_URL_OPTION, '');
        $isDefault = empty($logoUrl);
        if ($isDefault) {
            $logoUrl = \plugin_dir_url(\dirname(__FILE__, 4)) . self::DEFAULT_LOGO_RELATIVE_PATH;
        }
        \wp_send_json_success(['logo_url' => $logoUrl, 'is_default' => $isDefault]);
    }
    /**
     * Handles the logo file upload.
     *
     * @param array $file
     *
     * @return void
     */
    protected function handleLogoUpload(array $file) : void
    {
        $uploadOverrides = ['test_form' => \false, 'mimes' => ['jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif']];
        $uploadedFile = \wp_handle_upload($file, $uploadOverrides);
        if (isset($uploadedFile['error'])) {
            \wp_send_json_error(['message' => $uploadedFile['error']]);
        }
        \wp_send_json_success(['logo_url' => $uploadedFile['url'], 'message' => \__('Logo uploaded successfully.', 'worldline-for-woocommerce'), 'is_default' => \false]);
    }
    /**
     * Handles saving the logo URL on form submission.
     *
     * @param array $settings
     *
     * @return void
     */
    protected function handleLogoOnSave(array $settings) : void
    {
        $newLogoUrl = isset($settings['logo_url']) ? \sanitize_text_field(\wp_unslash($settings['logo_url'])) : '';
        $currentLogoUrl = (string) \get_option(self::LOGO_URL_OPTION, '');
        if ($newLogoUrl !== $currentLogoUrl) {
            if ($currentLogoUrl && !\str_contains($currentLogoUrl, self::DEFAULT_LOGO_RELATIVE_PATH)) {
                $oldPath = $this->getPathFromUrl($currentLogoUrl);
                if (\file_exists($oldPath)) {
                    \unlink($oldPath);
                }
            }
            if (\str_contains($newLogoUrl, self::DEFAULT_LOGO_RELATIVE_PATH)) {
                \update_option(self::LOGO_URL_OPTION, '');
                return;
            }
            \update_option(self::LOGO_URL_OPTION, $newLogoUrl);
        }
    }
    /**
     * Returns the full path to the logo file from its URL.
     *
     * @param string $url
     *
     * @return string
     */
    private function getPathFromUrl(string $url) : string
    {
        $uploadDir = \wp_upload_dir();
        return \str_replace($uploadDir['baseurl'], $uploadDir['basedir'], $url);
    }
}
