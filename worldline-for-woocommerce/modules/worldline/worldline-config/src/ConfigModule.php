<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Config;

use Worldline\Vendor\Dhii\Validation\Exception\ValidationFailedExceptionInterface;
use Worldline\Vendor\Dhii\Validator\CallbackValidator;
use Worldline\Vendor\enshrined\svgSanitize\Sanitizer;
use Worldline\Assets\Asset;
use Worldline\Assets\AssetManager;
use Worldline\Assets\Script;
use Worldline\Assets\Style;
use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentGateway;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Psr\Container\ContainerExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Container\NotFoundExceptionInterface;
class ConfigModule implements ExecutableModule, ServiceModule
{
    use ModuleClassNameIdTrait;
    public const CUSTOM_ICONS_OPTION = 'worldline_custom_icons';
    /**
     * Shared nonce action for the config settings admin AJAX endpoints
     * (logo upload + custom icon upload).
     */
    public const ADMIN_NONCE_ACTION = 'wlop_config_admin';
    /**
     * Action name for the nonce protecting the configuration AJAX handlers.
     */
    public const AJAX_NONCE = 'wlop_config_nonce';
    /**
     * Capability required to use the configuration AJAX handlers.
     */
    public const AJAX_CAPABILITY = 'manage_woocommerce';
    /**
     * Maximum number of custom icon files accepted in a single upload request.
     */
    public const MAX_ICON_FILES = 20;
    /**
     * Maximum size, in bytes, of a single custom icon file (2 MB).
     */
    public const MAX_ICON_FILE_SIZE = 2097152;
    public const LOGO_URL_OPTION = 'logo_url';
    public const DEFAULT_LOGO_RELATIVE_PATH = 'modules/worldline/worldline-payment-gateway/assets/images/worldline-logo.svg';
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
            $assetManager->register((new Script("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'backend-main.js'), Asset::BACKEND))->withLocalize('wlopConfig', static function () : array {
                return ['nonce' => \wp_create_nonce(self::ADMIN_NONCE_ACTION)];
            })->withTranslation('worldline-for-woocommerce', \WP_PLUGIN_DIR . '/worldline-for-woocommerce/languages/'), new Style("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'backend-main.css'), Asset::BACKEND));
        });
        \add_filter('woocommerce_settings_api_sanitized_fields_' . GatewayIds::HOSTED_CHECKOUT, static function (array $settings) use($container, $self) : array {
            $gateway = $container->get('worldline_payment_gateway.gateway');
            \assert($gateway instanceof PaymentGateway);
            $old_settings = \get_option($gateway->get_option_key(), []);
            $connectionValidator = $container->get('config.connection_validator');
            \assert($connectionValidator instanceof CallbackValidator);
            try {
                $connectionValidator->validate($settings);
            } catch (ValidationFailedExceptionInterface $exc) {
                $gateway->add_error(\__('Connection to the Worldline API failed. Check the credentials.', 'worldline-for-woocommerce'));
                return \is_array($old_settings) ? $old_settings : [];
            }
            list($settings, $errors) = $self->validateAndReorderWebhooks($settings, $old_settings);
            foreach ($errors as $error) {
                $gateway->add_error($error);
            }
            $self->handleLogoOnSave($settings);
            return $settings;
        });
        \add_action('wp_ajax_wlop_hosted_tokenization_logo', function () use($container) {
            $this->handleConfigAjax($container);
        });
        \add_filter('woocommerce_settings_api_sanitized_fields_' . GatewayIds::HOSTED_TOKENIZATION, static function (array $settings) use($container, $self) : array {
            return $self->handleIconsOnSave($settings);
        });
        \add_filter("woocommerce_settings_api_form_fields_" . GatewayIds::HOSTED_TOKENIZATION, static function (array $formFields) {
            if (isset($formFields['card_icons'])) {
                $customIconsJson = \get_option('worldline_custom_icons', '[]');
                $customIcons = \json_decode($customIconsJson, \true) ?? [];
                $customBrands = [];
                foreach ($customIcons as $customIcon) {
                    $customBrands['custom_' . $customIcon['id']] = $customIcon['title'];
                }
                $filteredFields = \array_filter($formFields['card_icons']['options'], static function ($iconKey) use($customBrands) {
                    return !\str_starts_with($iconKey, 'custom_') || \array_key_exists($iconKey, $customBrands);
                }, \ARRAY_FILTER_USE_KEY);
                $formFields['card_icons']['options'] = \array_merge($filteredFields, $customBrands);
            }
            return $formFields;
        });
        \add_action('wp_ajax_wlop_upload_custom_icon', static function () use($self) {
            $self->handleIconUpload();
        });
        \add_action('wp_ajax_wlop_get_custom_icons', static function () use($self) {
            $self->handleGetCustomIcons();
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
            if (!\current_user_can('manage_woocommerce')) {
                \wp_send_json_error(['message' => \__('Forbidden.', 'worldline-for-woocommerce')], 403);
                return;
            }
            $nonce = isset($_POST['nonce']) ? \sanitize_text_field(\wp_unslash($_POST['nonce'])) : '';
            if (!\wp_verify_nonce($nonce, self::ADMIN_NONCE_ACTION)) {
                \wp_send_json_error(['message' => \__('Invalid security token.', 'worldline-for-woocommerce')], 403);
                return;
            }
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
        $uploadedFile = $this->uploadImage($file, ['jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'svg' => 'image/svg+xml']);
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
            if ($currentLogoUrl && \strpos($currentLogoUrl, self::DEFAULT_LOGO_RELATIVE_PATH) === \false) {
                $oldPath = $this->getPathFromUrl($currentLogoUrl);
                if (\file_exists($oldPath)) {
                    \unlink($oldPath);
                }
            }
            if (\strpos($newLogoUrl, self::DEFAULT_LOGO_RELATIVE_PATH) !== \false) {
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
    /**
     * Validates and re-orders the additional webhook URLs for the settings page.
     *
     * @param array $settings
     * @param array $old_settings
     *
     * @return array{0: array, 1: array}
     */
    protected function validateAndReorderWebhooks(array $settings, array $old_settings) : array
    {
        $urlFields = ['additional_webhook_url_1', 'additional_webhook_url_2', 'additional_webhook_url_3', 'additional_webhook_url_4'];
        $fieldTitles = ['additional_webhook_url_1' => \__('Additional Webhook URL 1', 'worldline-for-woocommerce'), 'additional_webhook_url_2' => \__('Additional Webhook URL 2', 'worldline-for-woocommerce'), 'additional_webhook_url_3' => \__('Additional Webhook URL 3', 'worldline-for-woocommerce'), 'additional_webhook_url_4' => \__('Additional Webhook URL 4', 'worldline-for-woocommerce')];
        $validUrls = [];
        $errors = [];
        foreach ($urlFields as $fieldId) {
            $url = isset($settings[$fieldId]) ? \sanitize_text_field(\wp_unslash($settings[$fieldId])) : '';
            if (empty($url)) {
                $settings[$fieldId] = '';
                continue;
            }
            if (\mb_strlen($url) > 325) {
                $errors[] = \sprintf(\__('%s: URL is too long (maximum 325 characters).', 'worldline-for-woocommerce'), $fieldTitles[$fieldId]);
                $url = $old_settings[$fieldId] ?? '';
                if (!empty($url)) {
                    $validUrls[] = $url;
                }
                continue;
            }
            if (!$this->isValidHttpsUrl($url)) {
                $errors[] = \sprintf(\__('%s: URL must be a valid URL starting with "https://" and must include a proper hostname (e.g., example.com).', 'worldline-for-woocommerce'), $fieldTitles[$fieldId]);
                $url = $old_settings[$fieldId] ?? '';
                if (!empty($url)) {
                    $validUrls[] = $url;
                }
                continue;
            }
            $validUrls[] = $url;
        }
        foreach ($urlFields as $index => $fieldId) {
            $settings[$fieldId] = $validUrls[$index] ?? '';
        }
        return [$settings, $errors];
    }
    /**
     * @param string $url The URL string to validate.
     *
     * @return bool True if the URL is a valid HTTPS URL with a valid host structure, false otherwise.
     */
    protected function isValidHttpsUrl(string $url) : bool
    {
        $isValidUrl = \filter_var($url, \FILTER_VALIDATE_URL);
        if (!$isValidUrl) {
            return \false;
        }
        $parts = \parse_url($url);
        if (!isset($parts['scheme']) || \strtolower($parts['scheme']) !== 'https') {
            return \false;
        }
        if (!isset($parts['host'])) {
            return \false;
        }
        $hostname = $parts['host'];
        if (\strpos($hostname, '.') === \false) {
            return \false;
        }
        $hostParts = \explode('.', $hostname);
        $tld = \end($hostParts);
        if (\strlen($tld) < 1) {
            return \false;
        }
        return \true;
    }
    protected function handleGetCustomIcons() : void
    {
        $savedIconsJson = \get_option(self::CUSTOM_ICONS_OPTION, '[]');
        $savedIcons = \json_decode($savedIconsJson, \true) ?? [];
        \wp_send_json_success(['icons' => $savedIcons]);
    }
    /**
     * Sanitize an uploaded SVG in place so embedded scripts, event handlers or
     * external references cannot execute in the admin/store context (SEC-002).
     * Non-SVG uploads pass through. Returns false (after removing the file, and
     * emitting a 400 on sanitizer failure) when the upload must be rejected.
     * Serving hardening (Content-Disposition: attachment / CSP) belongs in the
     * web-server / CDN config, since WordPress serves uploads directly.
     *
     * @param array<string, mixed> $uploadedFile
     */
    private function sanitizeUploadedSvg(array $uploadedFile) : bool
    {
        if (($uploadedFile['type'] ?? '') !== 'image/svg+xml') {
            return \true;
        }
        $svgContents = \file_get_contents($uploadedFile['file']);
        if ($svgContents === \false) {
            $this->deleteFile($uploadedFile['file']);
            return \false;
        }
        $sanitizedSvg = (new Sanitizer())->sanitize($svgContents);
        if ($sanitizedSvg === \false) {
            $this->deleteFile($uploadedFile['file']);
            \wp_send_json_error(['message' => \__('The uploaded SVG could not be sanitized and was rejected.', 'worldline-for-woocommerce')], 400);
            return \false;
        }
        \file_put_contents($uploadedFile['file'], $sanitizedSvg);
        return \true;
    }
    // phpcs:ignore Worldline.CodeQuality.FunctionLength.TooLong -- capability + nonce guard kept inline so WordPress.Security.NonceVerification recognises it.
    protected function handleIconUpload() : void
    {
        if (!\current_user_can('manage_woocommerce')) {
            \wp_send_json_error(['message' => \__('Forbidden.', 'worldline-for-woocommerce')], 403);
            return;
        }
        $nonce = isset($_POST['nonce']) ? \sanitize_text_field(\wp_unslash($_POST['nonce'])) : '';
        if (!\wp_verify_nonce($nonce, self::ADMIN_NONCE_ACTION)) {
            \wp_send_json_error(['message' => \__('Invalid security token.', 'worldline-for-woocommerce')], 403);
            return;
        }
        if (empty($_FILES['icon_files'])) {
            \wp_send_json_error(['message' => \__('No files uploaded.', 'worldline-for-woocommerce')]);
            return;
        }
        $uploadedIcons = [];
        $files = $_FILES['icon_files'];
        if (\is_array($files['name'])) {
            if (\count($files['name']) > self::MAX_ICON_FILES) {
                \wp_send_json_error(['message' => \sprintf(
                    /* translators: %d: maximum number of files. */
                    \__('You can upload at most %d icons at a time.', 'worldline-for-woocommerce'),
                    self::MAX_ICON_FILES
                )]);
                return;
            }
            foreach ($files['name'] as $index => $name) {
                if ($files['error'][$index] !== \UPLOAD_ERR_OK) {
                    continue;
                }
                $file = ['name' => $files['name'][$index], 'type' => $files['type'][$index], 'tmp_name' => $files['tmp_name'][$index], 'error' => $files['error'][$index], 'size' => $files['size'][$index]];
                $result = $this->processSingleIconUpload($file);
                if ($result) {
                    $uploadedIcons[] = $result;
                }
            }
        } else {
            if ($files['error'] === \UPLOAD_ERR_OK) {
                $result = $this->processSingleIconUpload($files);
                if ($result) {
                    $uploadedIcons[] = $result;
                }
            }
        }
        if (empty($uploadedIcons)) {
            \wp_send_json_error(['message' => \__('Failed to upload icons.', 'worldline-for-woocommerce')]);
            return;
        }
        \wp_send_json_success(['icons' => $uploadedIcons, 'message' => \__('Icon(s) uploaded successfully.', 'worldline-for-woocommerce')]);
    }
    private function processSingleIconUpload(array $file) : ?array
    {
        if (isset($file['size']) && (int) $file['size'] > self::MAX_ICON_FILE_SIZE) {
            return null;
        }
        $uploadedFile = $this->uploadImage($file, ['jpg|jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'svg' => 'image/svg+xml']);
        if (isset($uploadedFile['error'])) {
            return null;
        }
        if (!$this->sanitizeUploadedSvg($uploadedFile)) {
            return null;
        }
        $attachmentId = \wp_insert_attachment(['post_mime_type' => $uploadedFile['type'], 'post_title' => \sanitize_text_field((string) \preg_replace('/\\.[^.]+$/', '', \basename($uploadedFile['file']))), 'post_content' => '', 'post_status' => 'inherit'], $uploadedFile['file']);
        if (\is_wp_error($attachmentId)) {
            return null;
        }
        \wp_update_attachment_metadata($attachmentId, \wp_generate_attachment_metadata($attachmentId, $uploadedFile['file']));
        return ['id' => $attachmentId, 'title' => \get_the_title($attachmentId), 'url' => $uploadedFile['url']];
    }
    protected function handleIconsOnSave(array $settings) : array
    {
        $newIconsJson = isset($settings['custom_icons']) ? \sanitize_text_field(\wp_unslash($settings['custom_icons'])) : '[]';
        $newIcons = \json_decode($newIconsJson, \true) ?? [];
        $oldIconsJson = \get_option(self::CUSTOM_ICONS_OPTION, '[]');
        $oldIcons = \json_decode($oldIconsJson, \true) ?? [];
        $oldIconIds = \array_column($oldIcons, 'id');
        $newIconIds = \array_column($newIcons, 'id');
        $idsToDelete = \array_diff($oldIconIds, $newIconIds);
        foreach ($idsToDelete as $attachmentId) {
            \wp_delete_attachment($attachmentId, \true);
        }
        $settings['card_icons'] = \array_filter($settings['card_icons'], static function ($icon) use($idsToDelete) {
            return !\str_starts_with($icon, 'custom_') || !\in_array(\explode('custom_', $icon)[1], $idsToDelete);
        });
        if (empty($newIcons)) {
            \delete_option(self::CUSTOM_ICONS_OPTION);
        } else {
            \update_option(self::CUSTOM_ICONS_OPTION, $newIconsJson);
        }
        return $settings;
    }
    /**
     * Remove an uploaded file if it still exists on disk.
     */
    private function deleteFile(string $path) : void
    {
        if (\file_exists($path)) {
            \unlink($path);
        }
    }
    /**
     * @param array $file
     * @param array<string, string> $mimes
     *
     * @return array
     */
    private function uploadImage(array $file, array $mimes) : array
    {
        $isSvg = \strtolower((string) \pathinfo((string) ($file['name'] ?? ''), \PATHINFO_EXTENSION)) === 'svg';
        if ($isSvg && !$this->sanitizeSvg((string) $file['tmp_name'])) {
            return ['error' => \__('The SVG file could not be processed.', 'worldline-for-woocommerce')];
        }
        $allowSvg = static function (array $types, $file, string $filename) : array {
            if (\strtolower((string) \pathinfo($filename, \PATHINFO_EXTENSION)) === 'svg') {
                $types['ext'] = 'svg';
                $types['type'] = 'image/svg+xml';
            }
            return $types;
        };
        if ($isSvg) {
            \add_filter('wp_check_filetype_and_ext', $allowSvg, 10, 3);
        }
        $uploadedFile = \wp_handle_upload($file, ['test_form' => \false, 'mimes' => $mimes]);
        if ($isSvg) {
            \remove_filter('wp_check_filetype_and_ext', $allowSvg, 10);
        }
        return $uploadedFile;
    }
    /**
     * @param string $path
     *
     * @return bool
     */
    private function sanitizeSvg(string $path) : bool
    {
        $contents = \file_get_contents($path);
        if ($contents === \false) {
            return \false;
        }
        $clean = (new Sanitizer())->sanitize($contents);
        if (!\is_string($clean) || $clean === '') {
            return \false;
        }
        return \file_put_contents($path, $clean) !== \false;
    }
}
