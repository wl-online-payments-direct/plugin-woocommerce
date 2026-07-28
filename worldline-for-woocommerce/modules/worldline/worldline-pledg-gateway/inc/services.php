<?php

declare (strict_types=1);
namespace Worldline\Vendor;

// phpcs:disable Worldline.CodeQuality.LineLength.TooLong
use Worldline\Vendor\Dhii\Services\Factories\Alias;
use Worldline\Vendor\Dhii\Services\Factories\Constructor;
use Worldline\Vendor\Dhii\Services\Factories\Value;
use Worldline\Vendor\Dhii\Services\Factory;
use Worldline\Vendor\Dhii\Services\Service;
use Worldline\Vendor\Worldline\PaymentGateway\DefaultIconsRenderer;
use Worldline\Vendor\Worldline\PaymentGateway\Icon;
use Worldline\Vendor\Worldline\PaymentGateway\IconProviderInterface;
use Worldline\Vendor\Worldline\PaymentGateway\StaticIconProvider;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\PledgGateway\PledgGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\PledgGateway\Payment\PledgRequestModifier;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Worldline\Vendor\Psr\Container\ContainerInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::PLEDG;
    return [
        // Form fields definition
        "payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"),
        // Titles & descriptions
        "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Sofinco (Worldline)', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.title" => new Factory([], static function () use($gatewayId) : string {
            $settings = (array) \get_option("woocommerce_{$gatewayId}_settings", []);
            $custom = isset($settings['title']) && \is_string($settings['title']) ? \trim($settings['title']) : '';
            if ($custom !== '') {
                return $custom;
            }
            return \__('Pay in installments', 'worldline-for-woocommerce');
        }),
        "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Pay easily in instalments with Sofinco. Merchant is paid upfront.', 'worldline-for-woocommerce'),
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
