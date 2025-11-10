<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Value;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Package;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\DefaultIconsRenderer;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Icon;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\IconProviderInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\StaticIconProvider;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\ConfigContainer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\ConfigModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin\RenderCaptureAction;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin\StatusUpdateAction;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AmountOfMoneyFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\MerchantClientFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\AuthorizedPaymentProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Cli\StatusUpdateCommand;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Cron\AutoCaptureHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Customer\AccountTypeHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Fee\FeeFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Notice\OrderActionNotice;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\DetailsDroppingMismatchHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\PaymentCaptureValidator;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\PaymentMismatchValidator;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Refund\RefundProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Refund\RefundValidator;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Shipping\AddressIndicatorHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\CardThreeDSecureFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\CarteBancaireThreeDSecureFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\ExemptionAmountChecker;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\GooglePayThreeDSecureFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Validator\CurrencySupportValidator;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WorldlinePaymentGatewayModule;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Logging\CommunicatorLogger;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $config = (require \dirname(__FILE__, 5) . '/config.php');
    $gatewayId = GatewayIds::HOSTED_CHECKOUT;
    return [
        'worldline_payment_gateway.gateway' => new Factory([], static function () use($gatewayId) : PaymentGateway {
            if (!\did_action('plugins_loaded')) {
                throw new \RuntimeException("Service 'worldline_payment_gateway.gateway' called too early.");
            }
            $gateways = \WC()->payment_gateways()->payment_gateways();
            $gateway = $gateways[$gatewayId] ?? null;
            if (!$gateway instanceof PaymentGateway) {
                throw new \RuntimeException("Gateway {$gatewayId} not found.");
            }
            return $gateway;
        }),
        'config.logo_url' => new Factory(['config.container'], static function (ConfigContainer $configContainer) : string {
            return $configContainer->get(ConfigModule::LOGO_URL_OPTION);
        }),
        'worldline_payment_gateway.api.default_test_endpoint' => new Value($config['TEST_ENDPOINT']),
        'worldline_payment_gateway.api.default_live_endpoint' => new Value($config['LIVE_ENDPOINT']),
        'worldline_payment_gateway.api.integrator-name' => new Value('Inpsyde'),
        'worldline_payment_gateway.api.client.logger' => new Factory(['core.is_debug_logging_enabled', 'worldline_logging.sdk_logger'], static function (bool $debugLogging, CommunicatorLogger $sdkLogger) : ?CommunicatorLogger {
            if (!$debugLogging) {
                return null;
            }
            return $sdkLogger;
        }),
        'worldline_payment_gateway.api.client.factory' => new Constructor(MerchantClientFactory::class, [Package::PROPERTIES, 'core.wp_environment', 'worldline_payment_gateway.api.integrator-name', 'worldline_payment_gateway.api.client.logger']),
        'worldline_payment_gateway.api.client' => new Factory(['worldline_payment_gateway.api.client.factory', 'config.pspid', 'config.api_key', 'config.api_secret', 'config.api_endpoint'], static function (MerchantClientFactory $factory, string $pspid, string $apiKey, string $apiSecret, string $apiEndpoint) : MerchantClientInterface {
            return $factory->create($pspid, $apiKey, $apiSecret, $apiEndpoint);
        }),
        'worldline_payment_gateway.order_updater' => new Constructor(OrderUpdater::class, ['worldline_payment_gateway.api.client', 'utils.locker.file_based_locker_factory', 'worldline_payment_gateway.money_amount_converter', 'worldline_payment_gateway.fee_factory']),
        "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage) : HostedPaymentProcessor {
            return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage);
        }),
        "payment_gateway.{$gatewayId}.refund_processor" => new Constructor(RefundProcessor::class, ['worldline_payment_gateway.api.client', 'worldline_payment_gateway.amount_of_money_factory', 'worldline_payment_gateway.refund_validator']),
        'worldline_payment_gateway.refund_validator' => new Constructor(RefundValidator::class),
        "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__($config['GATEWAY_METHOD_TITLE'], 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.title" => new Factory(['config.primary_gateway_title'], static function (string $customTitle) : string {
            if (!empty($customTitle)) {
                return $customTitle;
            }
            return \__('Worldline Global Online Pay', 'worldline-for-woocommerce');
        }),
        "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Accept payments with all major and local payment methods.', 'worldline-for-woocommerce'),
        "payment_gateway.{$gatewayId}.description" => new Factory(['config.surcharge_enabled'], static function (bool $surchargeEnabled) : string {
            if ($surchargeEnabled) {
                return \__('Accepting all major and local payment options. Final price may differ at checkout due to possible surcharges.', 'worldline-for-woocommerce');
            }
            return \__('Accepting all major and local payment options.', 'worldline-for-woocommerce');
        }),
        "payment_gateway.{$gatewayId}.supports" => static function () : array {
            return ['products', 'refunds', 'tokenization'];
        },
        "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'),
        "payment_gateway.{$gatewayId}.availability_callback" => new Factory(['worldline_payment_gateway.currency_support_validator'], static function (CurrencySupportValidator $currencySupportValidator) : callable {
            return static function () use($currencySupportValidator) : bool {
                return $currencySupportValidator->wlopSupportStoreCurrency();
            };
        }),
        "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['config.logo_url', 'assets.get_module_static_asset_url'], static function (string $logoUrl, callable $getStaticAssetUrl) : IconProviderInterface {
            $src = $logoUrl !== '' ? $logoUrl : $getStaticAssetUrl(WorldlinePaymentGatewayModule::PACKAGE_NAME, "images/worldline-logo.svg");
            $getStaticAssetUrl(WorldlinePaymentGatewayModule::PACKAGE_NAME, "images/worldline-logo.svg");
            $icon = new Icon('worldline-logo', $src, 'Worldline logo');
            return new StaticIconProvider($icon);
        }),
        "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"]),
        "payment_gateway.{$gatewayId}.icon" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : string {
            return $getStaticAssetUrl(WorldlinePaymentGatewayModule::PACKAGE_NAME, "images/worldline-gateway-logo.svg");
        }),
        'worldline_payment_gateway.transformer.wc_order_to_wlop_order' => Service::fromFile("{$moduleRoot}/inc/transformers/wc-order-to-wlop-order.php"),
        'worldline_payment_gateway.wc_order_factory' => new Constructor(WcOrderBasedOrderFactory::class, ['worldline_payment_gateway.transformer.wc_order_to_wlop_order', 'worldline_payment_gateway.payment_mismatch_validator', 'worldline_payment_gateway.details_dropping_mismatch_handler', 'config.surcharge_enabled', 'config.send_shopping_cart']),
        'worldline_payment_gateway.amount_of_money_factory' => new Constructor(AmountOfMoneyFactory::class, ['worldline_payment_gateway.money_amount_converter']),
        'worldline_payment_gateway.transformer.hosted_checkout_request' => Service::fromFile("{$moduleRoot}/inc/transformers/hosted-checkout-request.php"),
        'worldline_payment_gateway.hosted_checkout_url_factory' => new Constructor(HostedCheckoutUrlFactory::class, ['worldline_payment_gateway.api.client', 'worldline_payment_gateway.transformer.hosted_checkout_request', 'config.surcharge_enabled']),
        'worldline_payment_gateway.locale' => static function () : string {
            $locale = \get_user_locale();
            $parts = \explode('_', $locale);
            // WL does not support locales like de_DE_formal, so returning just de_DE
            return \implode('_', \array_slice($parts, 0, 2));
        },
        'worldline_payment_gateway.wc_base_country' => static function () : string {
            return \WC()->countries->get_base_country();
        },
        'worldline_payment_gateway.hosted_checkout_language' => new Factory(['worldline_payment_gateway.wc_base_country'], static function (string $wcBaseCountry) : ?string {
            $countriesAndLanguages = ['DE' => 'de_DE', 'FR' => 'fr_FR', 'NL' => 'nl_NL', 'ES' => 'es_ES', 'IT' => 'it_IT'];
            if (isset($countriesAndLanguages[$wcBaseCountry])) {
                return $countriesAndLanguages[$wcBaseCountry];
            }
            return null;
        }),
        'worldline_payment_gateway.admin.render_capture_action' => new Constructor(RenderCaptureAction::class, ['worldline_payment_gateway.payment_capture_validator']),
        'worldline_payment_gateway.admin.status_update_action' => new Constructor(StatusUpdateAction::class, ['worldline_payment_gateway.order_updater']),
        'worldline_payment_gateway.cli.status_update_command' => new Constructor(StatusUpdateCommand::class, ['worldline_payment_gateway.order_updater']),
        'worldline_payment_gateway.authorized_payment_processor' => new Constructor(AuthorizedPaymentProcessor::class, ['worldline_payment_gateway.api.client', 'worldline_payment_gateway.payment_capture_validator', 'worldline_payment_gateway.money_amount_converter', 'worldline_payment_gateway.order_action_notice']),
        'worldline_payment_gateway.payment_capture_validator' => new Constructor(PaymentCaptureValidator::class),
        'worldline_payment_gateway.money_amount_converter' => static function () : MoneyAmountConverter {
            return new MoneyAmountConverter();
        },
        'worldline_payment_gateway.order_action_notice' => static function () : OrderActionNotice {
            return new OrderActionNotice();
        },
        'worldline_payment_gateway.currency_support_validator' => new Constructor(CurrencySupportValidator::class, ['worldline_payment_gateway.api.client']),
        'worldline_payment_gateway.payment_mismatch_validator' => new Constructor(PaymentMismatchValidator::class, []),
        'worldline_payment_gateway.details_dropping_mismatch_handler' => static function () : DetailsDroppingMismatchHandler {
            return new DetailsDroppingMismatchHandler();
        },
        'worldline_payment_gateway.fee_factory' => static function () : FeeFactory {
            return new FeeFactory();
        },
        'worldline_payment_gateway.address_indicator_handler' => static fn() => new AddressIndicatorHandler(),
        'worldline_payment_gateway.account_type_handler' => static fn(): AccountTypeHandler => new AccountTypeHandler(),
        'worldline_payment_gateway.3ds.exemption_amount_checker' => new Constructor(ExemptionAmountChecker::class, ['config.3ds_exemption_limit']),
        'worldline_payment_gateway.3ds.card_3ds_factory' => new Constructor(CardThreeDSecureFactory::class, ['config.enable_3ds', 'config.enforce_3dsv2', 'config.3ds_exemption_type', 'worldline_payment_gateway.3ds.exemption_amount_checker']),
        'worldline_payment_gateway.3ds.google_pay_3ds_factory' => new Constructor(GooglePayThreeDSecureFactory::class, ['config.enable_3ds', 'config.enforce_3dsv2', 'config.3ds_exemption_type', 'worldline_payment_gateway.3ds.exemption_amount_checker']),
        'worldline_payment_gateway.3ds.carte_bancaire_3ds_factory' => new Constructor(CarteBancaireThreeDSecureFactory::class, ['config.enable_3ds', 'config.3ds_exemption_type', 'worldline_payment_gateway.3ds.exemption_amount_checker', 'config.authorization_mode']),
        'worldline_payment_gateway.auto_capture.handler.interval' => static fn(): int => 3600,
        'worldline_payment_gateway.auto_capture.handler' => new Constructor(AutoCaptureHandler::class, ['config.capture_mode', 'worldline_payment_gateway.api.client']),
        // phpcs:disable WordPress.Security.NonceVerification
        'worldline_payment_gateway.customer_screen_height' => static function () : ?int {
            $key = 'wlop_screen_height';
            if (isset($_POST[$key]) && \is_numeric($_POST[$key])) {
                return (int) $_POST[$key];
            }
            return null;
        },
        'worldline_payment_gateway.customer_screen_width' => static function () : ?int {
            $key = 'wlop_screen_width';
            if (isset($_POST[$key]) && \is_numeric($_POST[$key])) {
                return (int) $_POST[$key];
            }
            return null;
        },
        'worldline_payment_gateway.customer_color_depth' => static function () : ?int {
            $key = 'wlop_color_depth';
            if (isset($_POST[$key]) && \is_numeric($_POST[$key])) {
                return (int) $_POST[$key];
            }
            return null;
        },
        'worldline_payment_gateway.customer_java_enabled' => static function () : ?bool {
            $key = 'wlop_java_enabled';
            if (isset($_POST[$key])) {
                return 'true' === $_POST[$key];
            }
            return null;
        },
        'worldline_payment_gateway.customer_timezone_offset' => static function () : ?int {
            $key = 'wlop_timezone_offset';
            if (isset($_POST[$key]) && \is_numeric($_POST[$key])) {
                return (int) $_POST[$key];
            }
            return null;
        },
    ];
};
