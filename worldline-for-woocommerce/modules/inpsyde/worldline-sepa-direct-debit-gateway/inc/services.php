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
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\SepaDirectDebitGateway\Payment\SepaDirectDebitRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\SepaDirectDebitGateway\SepaDirectDebitGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::SEPA_DIRECT_DEBIT;
    return [
        // Form fields definition
        "payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"),
        // Titles & descriptions
        "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('SEPA Direct Debit (Worldline)', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.title" => static fn(): string => \__('SEPA Direct Debit', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Pay via SEPA Direct Debit. Customer will be redirected to complete the SEPA mandate flow.', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.description" => static fn(): string => '',
        "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null,
        "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'),
        "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'sepa_direct_debit.request_modifier'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, SepaDirectDebitRequestModifier $sepaDirectDebitRequestModifier) : HostedPaymentProcessor {
            return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $sepaDirectDebitRequestModifier);
        }),
        "payment_gateway.{$gatewayId}.supports" => static fn(): array => ['products', 'refunds'],
        "payment_gateway.{$gatewayId}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'),
        "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
            $src = $getStaticAssetUrl(SepaDirectDebitGatewayModule::PACKAGE_NAME, "images/sepa-direct-debit-logo.svg");
            $icon = new Icon('sepa-direct-debit-logo', $src, 'SEPA Direct Debit logo');
            return new StaticIconProvider($icon);
        }),
        "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"]),
        "sepa_direct_debit.request_modifier" => new Constructor(SepaDirectDebitRequestModifier::class, []),
        "sepa_direct_debit.availability.country_codes" => new Value([
            "AX",
            // Åland Islands
            "AD",
            // Andorra
            "AT",
            // Austria
            "BE",
            // Belgium
            "BG",
            // Bulgaria
            "HR",
            // Croatia
            "CY",
            // Cyprus
            "CZ",
            // Czech Republic
            "DK",
            // Denmark
            "EE",
            // Estonia
            "FI",
            // Finland
            "FR",
            // France
            "GF",
            // French Guiana
            "DE",
            // Germany
            "GR",
            // Greece
            "GL",
            // Greenland
            "GP",
            // Guadeloupe
            "HU",
            // Hungary
            "IS",
            // Iceland
            "IE",
            // Ireland
            "IT",
            // Italy
            "LV",
            // Latvia
            "LI",
            // Liechtenstein
            "LT",
            // Lithuania
            "LU",
            // Luxembourg
            "MT",
            // Malta
            "MQ",
            // Martinique
            "YT",
            // Mayotte
            "MC",
            // Monaco
            "NL",
            // Netherlands
            "NO",
            // Norway
            "PL",
            // Poland
            "PT",
            // Portugal
            "RE",
            // Réunion
            "RO",
            // Romania
            "BL",
            // Saint Barthélemy
            "MF",
            // Saint Martin
            "PM",
            // Saint Pierre and Miquelon
            "SM",
            // San Marino
            "SK",
            // Slovakia
            "SI",
            // Slovenia
            "ES",
            // Spain
            "SE",
            // Sweden
            "CH",
            // Switzerland
            "GB",
            // United Kingdom
            "VA",
        ]),
        "sepa_direct_debit.availability.currencies" => new Value(["EUR"]),
        "payment_gateway.{$gatewayId}.availability_callback" => static function (ContainerInterface $container) : callable {
            return static function () use($container) : bool {
                try {
                    $billingCountry = \WC()->customer ? \WC()->customer->get_billing_country() : '';
                    $currency = \get_woocommerce_currency();
                    $availableCountries = $container->get('sepa_direct_debit.availability.country_codes');
                    $availableCurrencies = $container->get('sepa_direct_debit.availability.currencies');
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
