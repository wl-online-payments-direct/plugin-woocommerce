<?php

declare (strict_types=1);
namespace Syde\Vendor;

// phpcs:disable Inpsyde.CodeQuality.LineLength
use Syde\Vendor\Dhii\Validator\CallbackValidator;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Config\ConfigContainer;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Config\Sanitizer\ApiEndpointSanitizer;
use Syde\Vendor\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Dhii\Services\Factory;
use Syde\Vendor\Dhii\Services\Service;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\MerchantClientFactory;
return static function (): array {
    $moduleRoot = \dirname(__FILE__, 2);
    return ['payment_gateway.worldline-for-woocommerce.form_fields' => Service::fromFile("{$moduleRoot}/inc/fields.php"), 'config.container' => new Constructor(ConfigContainer::class, ['worldline_payment_gateway.gateway']), 'config.connection_validator.callback' => new Factory(['worldline_payment_gateway.api.client.factory'], static function (MerchantClientFactory $clientFactory): callable {
        return static function (array $settings) use ($clientFactory): ?string {
            $pspid = (string) $settings['pspid'];
            $isLive = $settings['live_mode'] !== 'no';
            $apiKey = (string) $settings[$isLive ? 'live_api_key' : 'test_api_key'];
            $apiSecret = (string) $settings[$isLive ? 'live_api_secret' : 'test_api_secret'];
            $apiEndpoint = (string) $settings[$isLive ? 'live_api_endpoint' : 'test_api_endpoint'];
            try {
                $client = $clientFactory->create($pspid, $apiKey, $apiSecret, $apiEndpoint);
                $client->services()->testConnection();
                return null;
            } catch (\Exception $ex) {
                \do_action('wlop.auth_error', ['exception' => $ex]);
                return \__('Connection to the Worldline API failed. Check the credentials.', 'worldline-for-woocommerce');
            }
        };
    }), 'config.connection_validator' => new Constructor(CallbackValidator::class, ['config.connection_validator.callback']), 'config.is_live' => new Factory(['config.container'], static function (ConfigContainer $config): bool {
        return $config->get('live_mode') !== 'no';
    }), 'config.test_api_key' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('test_api_key');
    }), 'config.test_api_endpoint' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('test_api_endpoint');
    }), 'config.test_api_secret' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('test_api_secret');
    }), 'config.live_api_key' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('live_api_key');
    }), 'config.live_api_secret' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('live_api_secret');
    }), 'config.live_api_endpoint' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('live_api_endpoint');
    }), 'config.api_key' => new Factory(['config.test_api_key', 'config.live_api_key', 'config.is_live'], static function (string $testKey, string $liveKey, bool $isLive): string {
        return $isLive ? $liveKey : $testKey;
    }), 'config.api_secret' => new Factory(['config.test_api_secret', 'config.live_api_secret', 'config.is_live'], static function (string $testSecret, string $liveSecret, bool $isLive): string {
        return $isLive ? $liveSecret : $testSecret;
    }), 'config.api_endpoint' => new Factory(['config.test_api_endpoint', 'config.live_api_endpoint', 'config.is_live'], static function (string $testEndpoint, string $liveEndpoint, bool $isLive): string {
        return $isLive ? $liveEndpoint : $testEndpoint;
    }), 'config.pspid' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('pspid');
    }), 'config.debug_logging' => new Factory(['config.container'], static function (ConfigContainer $config): bool {
        return $config->get('debug_logging') !== 'no';
    }), 'config.authorization_mode' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        $authorizationMode = $config->get('authorization_mode');
        if ($authorizationMode === 'authorization') {
            $creditCardAuthorizationMode = $config->get('credit_card_authorization_mode');
            if ($creditCardAuthorizationMode === 'pre_authorization') {
                return AuthorizationMode::PRE_AUTHORIZATION;
            }
            return AuthorizationMode::FINAL_AUTHORIZATION;
        }
        return AuthorizationMode::SALE;
    }), 'config.enforce_3dsv2' => new Factory(['config.container'], static function (ConfigContainer $config): bool {
        return $config->get('enforce_3dsv2') === 'yes';
    }), 'config.card_brands_grouped' => new Factory(['config.container'], static function (ConfigContainer $config): bool {
        return $config->get('card_brands_display') === 'yes';
    }), 'config.stored_card_buttons' => new Factory(['config.container'], static function (ConfigContainer $config): bool {
        return $config->get('stored_card_buttons') !== 'no';
    }), 'config.payment_button_title' => new Factory(['config.container'], static function (ConfigContainer $config): string {
        return (string) $config->get('payment_button_title');
    }), 'config.is_hosted_checkout' => new Factory(['config.container'], static function (ConfigContainer $config): bool {
        return $config->get('checkout_type') === 'hosted';
    }), 'config.surcharge_enabled' => new Factory(['config.container'], static function (ConfigContainer $config): bool {
        return (string) $config->get('surcharge_enable') === 'yes';
    }), 'payment_gateway.worldline-for-woocommerce.order_button_text' => new Factory(['config.payment_button_title'], static function (string $paymentButtonTitleRaw): ?string {
        $paymentButtonTitle = \wp_strip_all_tags($paymentButtonTitleRaw);
        return $paymentButtonTitle !== '' ? $paymentButtonTitle : null;
    }), 'payment_gateway.worldline-for-woocommerce.settings_field_sanitizer.test_api_endpoint_field' => new Constructor(ApiEndpointSanitizer::class, ['uri.builder', 'worldline_payment_gateway.api.default_test_endpoint']), 'payment_gateway.worldline-for-woocommerce.settings_field_sanitizer.live_api_endpoint_field' => new Constructor(ApiEndpointSanitizer::class, ['uri.builder', 'worldline_payment_gateway.api.default_live_endpoint'])];
};
