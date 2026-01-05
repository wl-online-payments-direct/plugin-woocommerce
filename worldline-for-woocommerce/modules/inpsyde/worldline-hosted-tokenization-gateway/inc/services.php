<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\DefaultIconsRenderer;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Icon;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\IconProviderInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\StaticIconProvider;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\ConfigContainer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway\Gateway\TokensPaymentFieldsRenderer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway\HostedTokenizationGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway\Payment\HostedTokenizationPaymentProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::HOSTED_TOKENIZATION;
    return ['hosted_tokenization_gateway.gateway' => new Factory([], static function () use($gatewayId) : PaymentGateway {
        if (!\did_action('plugins_loaded')) {
            throw new \RuntimeException("Service 'hosted_tokenization_gateway.gateway' called too early.");
        }
        $gateways = \WC()->payment_gateways()->payment_gateways();
        $gateway = $gateways[$gatewayId] ?? null;
        if (!$gateway instanceof PaymentGateway) {
            throw new \RuntimeException("Gateway {$gatewayId} not found.");
        }
        return $gateway;
    }), "payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"), 'hosted_tokenization_gateway.config.container' => new Constructor(ConfigContainer::class, ['hosted_tokenization_gateway.gateway']), "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Credit cards (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayId}.title" => new Factory(['hosted_tokenization_gateway.config.container'], static function (ConfigContainer $config) : string {
        $customTitle = $config->get('title');
        if (!empty($customTitle)) {
            return $customTitle;
        }
        return \__('Credit cards', 'worldline-for-woocommerce');
    }), "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Accept payments with credit cards.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.description" => static fn(): string => '<div id="wlop_ht" class="wlop-ht-wrapper"></div>
         <div id="wlop_ht_surcharge_note" class="wlop-surcharge-note"></div>', "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayId}.payment_processor" => new Constructor(HostedTokenizationPaymentProcessor::class, ['payment_gateway.worldline-for-woocommerce.payment_processor', 'worldline_payment_gateway.wc_order_factory', 'worldline_payment_gateway.transformer.hosted_checkout_request', 'worldline_payment_gateway.api.client', 'config.authorization_mode', 'worldline_payment_gateway.3ds.card_3ds_factory', 'webhooks.notification_url', 'config.webhook_mode_is_automatic', 'config.additional_webhook_urls']), "payment_gateway.{$gatewayId}.supports" => static function () : array {
        return ['products', 'refunds', 'tokenization'];
    }, "payment_gateway.{$gatewayId}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayId}.availability_callback" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.availability_callback'), "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['hosted_tokenization_gateway.config.container', 'assets.get_module_static_asset_url'], static function (ConfigContainer $config, callable $getStaticAssetUrl) : IconProviderInterface {
        $src = static fn(string $handle): string => $getStaticAssetUrl(HostedTokenizationGatewayModule::PACKAGE_NAME, "images/{$handle}.svg");
        $alt = static fn(string $handle): string => "{$handle} icon";
        $icon = static fn(string $handle): Icon => new Icon($handle, $src($handle), $alt($handle));
        $selectedLogos = $config->get('card_icons');
        if (!\is_array($selectedLogos)) {
            $selectedLogos = [];
        }
        $customIconsJson = \get_option('worldline_custom_icons', '[]');
        $customIcons = \json_decode($customIconsJson, \true) ?? [];
        $customIconsLookup = [];
        foreach ($customIcons as $customIcon) {
            $customIconsLookup['custom_' . $customIcon['id']] = $customIcon;
        }
        $allIcons = [];
        foreach ($selectedLogos as $logoHandle) {
            if (\str_starts_with($logoHandle, 'custom_')) {
                if (isset($customIconsLookup[$logoHandle])) {
                    $customIcon = $customIconsLookup[$logoHandle];
                    $allIcons[] = new Icon($logoHandle, $customIcon['url'], $customIcon['title'] . ' icon');
                }
            } else {
                $allIcons[] = $icon($logoHandle);
            }
        }
        return new StaticIconProvider(...$allIcons);
    }), "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"]), 'hosted_tokenization_gateway.card_brands' => new Factory([], static function () : array {
        $predefinedBrands = ['amex' => \__('American Express', 'worldline-for-woocommerce'), 'bancontact' => \__('Bancontact', 'worldline-for-woocommerce'), 'cb' => \__('Cartes Bancaires', 'worldline-for-woocommerce'), 'diners' => \__('Diners Club', 'worldline-for-woocommerce'), 'discover' => \__('Discover', 'worldline-for-woocommerce'), 'jcb' => \__('JCB', 'worldline-for-woocommerce'), 'maestro' => \__('Maestro', 'worldline-for-woocommerce'), 'mastercard' => \__('Mastercard', 'worldline-for-woocommerce'), 'visa' => \__('Visa', 'worldline-for-woocommerce')];
        // Get custom icons and add them to the options
        $customIconsJson = \get_option('worldline_custom_icons', '[]');
        $customIcons = \json_decode($customIconsJson, \true) ?? [];
        $customBrands = [];
        foreach ($customIcons as $customIcon) {
            $customBrands['custom_' . $customIcon['id']] = $customIcon['title'];
        }
        return \array_merge($predefinedBrands, $customBrands);
    }), "payment_gateway.{$gatewayId}.payment_fields_renderer" => new Constructor(TokensPaymentFieldsRenderer::class, ['hosted_tokenization_gateway.gateway'])];
};
