<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Value;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\DefaultIconsRenderer;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Icon;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\IconProviderInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\StaticIconProvider;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Przelewy24Gateway\Przelewy24GatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Przelewy24Gateway\Payment\Przelewy24RequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::PRZELEWY24;
    return ["payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"), "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Przelewy24 (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.title" => static fn() => \__('Przelewy24', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Accept payments with Przelewy24.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.description" => static fn(): string => '', "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'przelewy24.request_modifier'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, Przelewy24RequestModifier $przelewy24RequestModifier) : HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $przelewy24RequestModifier);
    }), "payment_gateway.{$gatewayId}.supports" => static function () : array {
        return ['products', 'refunds'];
    }, "payment_gateway.{$gatewayId}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "przelewy24.availability.country_codes" => new Value(["PL", "DE"]), "przelewy24.availability.currencies" => new Value(["PLN"]), "payment_gateway.{$gatewayId}.availability_callback" => static function (ContainerInterface $container) : callable {
        return static function () use($container) : bool {
            try {
                $billingCountry = \WC()->customer ? \WC()->customer->get_billing_country() : '';
                $currency = \get_woocommerce_currency();
                $availableCountries = $container->get('przelewy24.availability.country_codes');
                $availableCurrencies = $container->get('przelewy24.availability.currencies');
                \assert(\is_array($availableCountries));
                \assert(\is_array($availableCurrencies));
                return \in_array(\strtoupper($billingCountry), $availableCountries, \true) && \in_array(\strtoupper($currency), $availableCurrencies, \true);
            } catch (\Throwable $exception) {
                return \false;
            }
        };
    }, "przelewy24.request_modifier" => new Constructor(Przelewy24RequestModifier::class, []), "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
        /** @var string $src */
        $src = $getStaticAssetUrl(Przelewy24GatewayModule::PACKAGE_NAME, "images/przelewy24-logo.svg");
        $icon = new Icon('przelewy24-logo', $src, 'Przelewy24 logo');
        return new StaticIconProvider($icon);
    }), "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"])];
};
