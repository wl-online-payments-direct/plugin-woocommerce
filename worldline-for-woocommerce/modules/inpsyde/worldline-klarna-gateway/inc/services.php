<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factories\FuncService;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Value;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\DefaultIconsRenderer;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Icon;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\IconProviderInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\StaticIconProvider;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\KlarnaGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\Payment\BankTransferRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\Payment\DirectDebitRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\Payment\FinancingRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\Payment\PayLaterRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\Payment\PayNowRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\Payment\PayWithKlarnaRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function (): array {
    $gatewayIdPayWithKlarna = GatewayIds::KLARNA_PAY_WITH_KLARNA;
    $gatewayIdPayNow = GatewayIds::KLARNA_PAY_NOW;
    $gatewayIdBankTransfer = GatewayIds::KLARNA_BANK_TRANSFER;
    $gatewayIdDirectDebit = GatewayIds::KLARNA_DIRECT_DEBIT;
    $gatewayIdPayLater = GatewayIds::KLARNA_PAY_LATER;
    $gatewayIdPayLaterPayIn3 = GatewayIds::KLARNA_PAY_LATER_PAY_IN_3;
    $gatewayIdPayLaterBankTransfer = GatewayIds::KLARNA_PAY_LATER_BANK_TRANSFER;
    $gatewayIdFinancing = GatewayIds::KLARNA_FINANCING;
    $gatewayIdFinancingPayIn3 = GatewayIds::KLARNA_FINANCING_PAY_IN_3;
    $getGatewayFormFields = static function (string $gatewayLabel): array {
        return ['enabled' => [
            'title' => \__('Enable/Disable', 'worldline-for-woocommerce'),
            'type' => 'checkbox',
            /* translators: %s: Name of the gateway. */
            'label' => \sprintf(\__('Enable %s', 'worldline-for-woocommerce'), $gatewayLabel),
            'default' => 'no',
        ]];
    };
    $klarnaGlobalServices = ['klarna.global.availability_checker' => static function (ContainerInterface $container): callable {
        return static function (array $languageCodes) use ($container): bool {
            global $woocommerce;
            try {
                $hostedCheckoutAvailabilityCallback = $container->get('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.availability_callback');
                $currencyExistForOneOfTheProducts = $hostedCheckoutAvailabilityCallback();
                if (!$currencyExistForOneOfTheProducts) {
                    return \false;
                }
                $billingCountry = $woocommerce->customer->get_billing_country();
                return \in_array($billingCountry, $languageCodes, \true);
            } catch (\Throwable $exception) {
                return \false;
            }
        };
    }, 'klarna.global.supports' => new Value(['products', 'refunds']), "klarna.global.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl): IconProviderInterface {
        /** @var string $src */
        $src = $getStaticAssetUrl(KlarnaGatewayModule::PACKAGE_NAME, "images/klarna-logo.svg");
        $icon = new Icon('klarna-logo', $src, 'Klarna logo');
        return new StaticIconProvider($icon);
    })];
    $payWithKlarnaServices = ["payment_gateway.{$gatewayIdPayWithKlarna}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Pay with Klarna (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdPayWithKlarna}.method_title" => static fn(): string => \__('Pay with Klarna (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayWithKlarna}.title" => static fn(): string => \__('Pay with Klarna', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayWithKlarna}.method_description" => static fn(): string => \__('Accept payments with Klarna.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayWithKlarna}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdPayWithKlarna}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdPayWithKlarna}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdPayWithKlarna}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', "klarna.{$gatewayIdPayWithKlarna}.request_modifier"], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, PayWithKlarnaRequestModifier $payWithKlarnaRequestModifier): HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $payWithKlarnaRequestModifier);
    }), "payment_gateway.{$gatewayIdPayWithKlarna}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdPayWithKlarna}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdPayWithKlarna}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["AT", "DE", "FI", "NL", "SE", "CH"]);
    }), "klarna.{$gatewayIdPayWithKlarna}.request_modifier" => new Constructor(PayWithKlarnaRequestModifier::class, []), "payment_gateway.{$gatewayIdPayWithKlarna}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdPayWithKlarna}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $payNowServices = ["payment_gateway.{$gatewayIdPayNow}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Pay Now (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdPayNow}.method_title" => static fn(): string => \__('Klarna - Pay Now (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayNow}.title" => static fn(): string => \__('Klarna - Pay Now', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayNow}.method_description" => static fn(): string => \__('Accept payments with Klarna - Pay Now.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayNow}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdPayNow}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdPayNow}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdPayNow}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', "klarna.{$gatewayIdPayNow}.request_modifier"], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, PayNowRequestModifier $payNowRequestModifier): HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $payNowRequestModifier);
    }), "payment_gateway.{$gatewayIdPayNow}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdPayNow}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdPayNow}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["AT", "DE", "FI", "NL", "SE", "CH"]);
    }), "klarna.{$gatewayIdPayNow}.request_modifier" => new Constructor(PayNowRequestModifier::class, []), "payment_gateway.{$gatewayIdPayNow}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdPayNow}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $bankTransferServices = ["payment_gateway.{$gatewayIdBankTransfer}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Bank transfer (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdBankTransfer}.method_title" => static fn(): string => \__('Klarna - Bank transfer (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdBankTransfer}.title" => static fn(): string => \__('Klarna - Bank transfer', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdBankTransfer}.method_description" => static fn(): string => \__('Accept payments with Klarna - Bank transfer.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdBankTransfer}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdBankTransfer}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdBankTransfer}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdBankTransfer}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', "klarna.{$gatewayIdBankTransfer}.request_modifier"], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, BankTransferRequestModifier $bankTransferRequestModifier): HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $bankTransferRequestModifier);
    }), "payment_gateway.{$gatewayIdBankTransfer}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdBankTransfer}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdBankTransfer}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["AT", "BE", "DE", "FI", "NL", "SE", "CH"]);
    }), "klarna.{$gatewayIdBankTransfer}.request_modifier" => new Constructor(BankTransferRequestModifier::class, []), "payment_gateway.{$gatewayIdBankTransfer}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdBankTransfer}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $directDebitServices = ["payment_gateway.{$gatewayIdDirectDebit}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Direct debit (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdDirectDebit}.method_title" => static fn(): string => \__('Klarna - Direct debit (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdDirectDebit}.title" => static fn(): string => \__('Klarna - Direct debit', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdDirectDebit}.method_description" => static fn(): string => \__('Accept payments with Klarna - Direct debit.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdDirectDebit}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdDirectDebit}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdDirectDebit}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdDirectDebit}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', "klarna.{$gatewayIdDirectDebit}.request_modifier"], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, DirectDebitRequestModifier $directDebitRequestModifier): HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $directDebitRequestModifier);
    }), "payment_gateway.{$gatewayIdDirectDebit}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdDirectDebit}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdDirectDebit}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["AT", "DE", "NL", "SE"]);
    }), "klarna.{$gatewayIdDirectDebit}.request_modifier" => new Constructor(DirectDebitRequestModifier::class, []), "payment_gateway.{$gatewayIdDirectDebit}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdDirectDebit}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $payLaterServices = ["payment_gateway.{$gatewayIdPayLater}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Pay later (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdPayLater}.method_title" => static fn(): string => \__('Klarna - Pay later (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLater}.title" => static fn(): string => \__('Klarna - Pay later', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLater}.method_description" => static fn(): string => \__('Accept payments with Klarna - Pay later.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLater}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdPayLater}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdPayLater}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdPayLater}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', "klarna.{$gatewayIdPayLater}.request_modifier"], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, PayLaterRequestModifier $payLaterRequestModifier): HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $payLaterRequestModifier);
    }), "payment_gateway.{$gatewayIdPayLater}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdPayLater}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdPayLater}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["AT", "DE", "DK", "FI", "NL", "NO", "PL", "SE", "GB", "BE", "CH"]);
    }), "klarna.{$gatewayIdPayLater}.request_modifier" => new Constructor(PayLaterRequestModifier::class, []), "payment_gateway.{$gatewayIdPayLater}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdPayLater}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $payLaterServicesPayIn3 = ["payment_gateway.{$gatewayIdPayLaterPayIn3}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Pay later - Pay in 3 (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdPayLaterPayIn3}.method_title" => static fn(): string => \__('Klarna - Pay later - Pay in 3 (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLaterPayIn3}.title" => static fn(): string => \__('Klarna - Pay later (Pay in 3)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLaterPayIn3}.method_description" => static fn(): string => \__('Accept payments with Klarna - Pay later.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLaterPayIn3}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdPayLaterPayIn3}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdPayLaterPayIn3}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdPayLaterPayIn3}.payment_processor" => new Alias("payment_gateway.{$gatewayIdPayLater}.payment_processor"), "payment_gateway.{$gatewayIdPayLaterPayIn3}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdPayLaterPayIn3}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdPayLaterPayIn3}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["FR", "ES", "IT", "IE", "PT"]);
    }), "payment_gateway.{$gatewayIdPayLaterPayIn3}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdPayLaterPayIn3}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $payLaterServicesBankTransfer = ["payment_gateway.{$gatewayIdPayLaterBankTransfer}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Pay later - Bank transfer (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.method_title" => static fn(): string => \__('Klarna - Pay later - Bank transfer (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.title" => static fn(): string => \__('Klarna - Pay later (Bank transfer)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.method_description" => static fn(): string => \__('Accept payments with Klarna - Pay later.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdPayLaterBankTransfer}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdPayLaterBankTransfer}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.payment_processor" => new Alias("payment_gateway.{$gatewayIdPayLater}.payment_processor"), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["IT", "ES"]);
    }), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdPayLaterBankTransfer}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $financingServices = ["payment_gateway.{$gatewayIdFinancing}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Financing (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdFinancing}.method_title" => static fn(): string => \__('Klarna - Financing (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdFinancing}.title" => static fn(): string => \__('Klarna - Financing', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdFinancing}.method_description" => static fn(): string => \__('Accept payments with Klarna - Financing.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdFinancing}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdFinancing}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdFinancing}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdFinancing}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', "klarna.{$gatewayIdFinancing}.request_modifier"], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, FinancingRequestModifier $financingRequestModifier): HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $financingRequestModifier);
    }), "payment_gateway.{$gatewayIdFinancing}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdFinancing}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdFinancing}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["AT", "DE", "FI", "NO", "SE", "GB"]);
    }), "klarna.{$gatewayIdFinancing}.request_modifier" => new Constructor(FinancingRequestModifier::class, []), "payment_gateway.{$gatewayIdFinancing}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdFinancing}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    $financingServicesPayIn3 = ["payment_gateway.{$gatewayIdFinancingPayIn3}.form_fields" => static fn(): array => $getGatewayFormFields(\__('Klarna - Financing - Pay in 3 (Worldline)', 'worldline-for-woocommerce')), "payment_gateway.{$gatewayIdFinancingPayIn3}.method_title" => static fn(): string => \__('Klarna - Financing - Pay in 3 (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdFinancingPayIn3}.title" => static fn(): string => \__('Klarna - Financing (Pay in 3)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdFinancingPayIn3}.method_description" => static fn(): string => \__('Accept payments with Klarna - Financing.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayIdFinancingPayIn3}.description" => static fn(): string => '', "payment_gateway.{$gatewayIdFinancingPayIn3}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayIdFinancingPayIn3}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayIdFinancingPayIn3}.payment_processor" => new Alias("payment_gateway.{$gatewayIdFinancing}.payment_processor"), "payment_gateway.{$gatewayIdFinancingPayIn3}.supports" => new Alias('klarna.global.supports'), "payment_gateway.{$gatewayIdFinancingPayIn3}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "payment_gateway.{$gatewayIdFinancingPayIn3}.availability_callback" => new FuncService(['klarna.global.availability_checker'], static function (PaymentGateway $paymentGateway, callable $availabilityChecker): bool {
        return $availabilityChecker(["NL", "GB"]);
    }), "payment_gateway.{$gatewayIdFinancingPayIn3}.method_icon_provider" => new Alias('klarna.global.method_icon_provider'), "payment_gateway.{$gatewayIdFinancingPayIn3}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["klarna.global.method_icon_provider"])];
    return \array_merge($klarnaGlobalServices, $payWithKlarnaServices, $payNowServices, $bankTransferServices, $directDebitServices, $payLaterServices, $payLaterServicesPayIn3, $payLaterServicesBankTransfer, $financingServices, $financingServicesPayIn3);
};
