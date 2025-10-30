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
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\BankTransferGateway\BankTransferGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\BankTransferGateway\Payment\BankTransferRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::BANK_TRANSFER;
    return ["payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"), "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Bank transfer (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.title" => static fn() => \__('Bank transfer', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Accept payments with Bank transfer.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.description" => static fn(): string => '', "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'bank_transfer.request_modifier'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, BankTransferRequestModifier $bankTransferRequestModifier) : HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $bankTransferRequestModifier);
    }), "payment_gateway.{$gatewayId}.supports" => static function () : array {
        return ['products', 'refunds'];
    }, "payment_gateway.{$gatewayId}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayId}.availability_callback" => static function (ContainerInterface $container) : callable {
        return static function () use($container) : bool {
            global $woocommerce;
            try {
                $hostedCheckoutAvailabilityCallback = $container->get('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.availability_callback');
                $currencyExistForOneOfTheProducts = $hostedCheckoutAvailabilityCallback();
                if (!$currencyExistForOneOfTheProducts) {
                    return \false;
                }
                $billingCountry = $woocommerce->customer->get_billing_country();
                $currency = \get_woocommerce_currency();
                $availableCountries = $container->get('bank_transfer.availability.country_codes');
                \assert(\is_array($availableCountries));
                $availableCurrencies = $container->get('bank_transfer.availability.currencies');
                \assert(\is_array($availableCurrencies));
                return \in_array($billingCountry, $availableCountries, \true) && \in_array($currency, $availableCurrencies, \true);
            } catch (\Throwable $exception) {
                return \false;
            }
        };
    }, "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
        /** @var string $src */
        $src = $getStaticAssetUrl(BankTransferGatewayModule::PACKAGE_NAME, "images/sepa-logo.svg");
        $icon = new Icon('sepa-logo', $src, 'SEPA logo');
        return new StaticIconProvider($icon);
    }), "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"]), "bank_transfer.request_modifier" => new Constructor(BankTransferRequestModifier::class, []), "bank_transfer.availability.country_codes" => new Value([
        // https://docs.direct.worldline-solutions.com/en/payment-methods-and-features/payment-methods/bank-transfer#countries-and-currencies
        "AT",
        // Austria
        "BE",
        // Belgium
        "HR",
        // Croatia
        "CZ",
        // Czech Republic
        "FR",
        // France
        "DE",
        // Germany
        "HU",
        // Hungary
        "IT",
        // Italy
        "LU",
        // Luxembourg
        "NL",
        // Netherlands
        "PL",
        // Poland
        "SK",
        // Slovakia
        "SI",
        // Slovenia
        "ES",
    ]), "bank_transfer.availability.currencies" => new Value([
        // https://docs.direct.worldline-solutions.com/en/payment-methods-and-features/payment-methods/bank-transfer#countries-and-currencies
        "CZK",
        "EUR",
        "HUF",
        "PLN",
    ])];
};
