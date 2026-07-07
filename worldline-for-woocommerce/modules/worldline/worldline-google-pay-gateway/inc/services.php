<?php

declare (strict_types=1);
namespace Worldline\Vendor;

// phpcs:disable Worldline.CodeQuality.LineLength.TooLong
use Worldline\Vendor\Dhii\Services\Factories\Alias;
use Worldline\Vendor\Dhii\Services\Factories\Constructor;
use Worldline\Vendor\Dhii\Services\Factory;
use Worldline\Vendor\Dhii\Services\Service;
use Worldline\Vendor\Worldline\PaymentGateway\DefaultIconsRenderer;
use Worldline\Vendor\Worldline\PaymentGateway\Icon;
use Worldline\Vendor\Worldline\PaymentGateway\IconProviderInterface;
use Worldline\Vendor\Worldline\PaymentGateway\StaticIconProvider;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\GooglePayGateway\GooglePayGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\GooglePayGateway\Payment\GooglePayRequestModifier;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::GOOGLE_PAY;
    return ["payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"), "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Google Pay (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.title" => new Factory([], static function () use($gatewayId) : string {
        $settings = (array) \get_option("woocommerce_{$gatewayId}_settings", []);
        $custom = isset($settings['title']) && \is_string($settings['title']) ? \trim($settings['title']) : '';
        if ($custom !== '') {
            return $custom;
        }
        return \__('Google Pay', 'worldline-for-woocommerce');
    }), "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Accept payments with Google Pay.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.description" => static fn(): string => '', "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'google_pay.request_modifier'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, GooglePayRequestModifier $googlePayRequestModifier) : HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $googlePayRequestModifier);
    }), "payment_gateway.{$gatewayId}.supports" => static function () : array {
        return ['products', 'refunds'];
    }, "payment_gateway.{$gatewayId}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayId}.availability_callback" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.availability_callback'), "google_pay.request_modifier" => new Constructor(GooglePayRequestModifier::class, ['worldline_payment_gateway.3ds.google_pay_3ds_factory']), "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
        /** @var string $src */
        $src = $getStaticAssetUrl(GooglePayGatewayModule::PACKAGE_NAME, "images/google-pay-logo.svg");
        $icon = new Icon('google-pay-logo', $src, 'Google Pay logo');
        return new StaticIconProvider($icon);
    }), "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"])];
};
