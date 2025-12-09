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
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PledgGateway\PledgGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PledgGateway\Payment\PledgRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::PLEDG;
    return [
        // Form fields definition
        "payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"),
        // Titles & descriptions
        "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Pledg (Worldline)', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.title" => static fn(): string => \__('Pledg', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Pay easily in instalments with Pledg. Merchant is paid upfront.', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.description" => static fn(): string => '',
        "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null,
        "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'),
        "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'pledg.request_modifier'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, PledgRequestModifier $pledgRequestModifier) : HostedPaymentProcessor {
            return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $pledgRequestModifier);
        }),
        "payment_gateway.{$gatewayId}.supports" => static fn(): array => ['products', 'refunds'],
        "payment_gateway.{$gatewayId}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'),
        "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
            $src = $getStaticAssetUrl(PledgGatewayModule::PACKAGE_NAME, "images/pledg.svg");
            $icon = new Icon('pledg-logo', $src, 'Pledg logo');
            return new StaticIconProvider($icon);
        }),
        "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"]),
        "pledg.request_modifier" => new Constructor(PledgRequestModifier::class, []),
        "pledg.availability.country_codes" => new Value(["FR"]),
        "pledg.availability.currencies" => new Value(["EUR"]),
        "payment_gateway.{$gatewayId}.availability_callback" => static function (ContainerInterface $container) : callable {
            return static function () use($container) : bool {
                try {
                    $billingCountry = \WC()->customer ? \WC()->customer->get_billing_country() : '';
                    $currency = \get_woocommerce_currency();
                    $availableCountries = $container->get('pledg.availability.country_codes');
                    $availableCurrencies = $container->get('pledg.availability.currencies');
                    \assert(\is_array($availableCountries));
                    \assert(\is_array($availableCurrencies));
                    return \in_array(\strtoupper($billingCountry), $availableCountries, \true) && \in_array(\strtoupper($currency), $availableCurrencies, \true);
                } catch (\Throwable $exception) {
                    return \false;
                }
            };
        },
    ];
};
