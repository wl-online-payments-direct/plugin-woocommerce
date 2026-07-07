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
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WeroGateway\Payment\WeroRefundProcessor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WeroGateway\Payment\WeroRequestModifier;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WeroGateway\WeroGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AmountOfMoneyFactory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Refund\RefundValidator;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::WERO;
    return ["payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"), "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Wero (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.title" => new Factory([], static function () use($gatewayId) : string {
        $settings = (array) \get_option("woocommerce_{$gatewayId}_settings", []);
        $custom = isset($settings['title']) && \is_string($settings['title']) ? \trim($settings['title']) : '';
        if ($custom !== '') {
            return $custom;
        }
        return \__('Wero', 'worldline-for-woocommerce');
    }), "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Accept payments with Wero.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.description" => static fn(): string => '', "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'wero.request_modifier'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, WeroRequestModifier $weroRequestModifier) : HostedPaymentProcessor {
        return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $weroRequestModifier);
    }), "payment_gateway.{$gatewayId}.supports" => static function () : array {
        return ['products', 'refunds'];
    }, "payment_gateway.{$gatewayId}.refund_processor" => new Factory(['worldline_payment_gateway.api.client', 'worldline_payment_gateway.amount_of_money_factory', 'worldline_payment_gateway.refund_validator'], static function (MerchantClientInterface $apiClient, AmountOfMoneyFactory $amountOfMoneyFactory, RefundValidator $refundValidator) : WeroRefundProcessor {
        return new WeroRefundProcessor($apiClient, $amountOfMoneyFactory, $refundValidator);
    }), "payment_gateway.{$gatewayId}.availability_callback" => static function () : callable {
        return static function () : bool {
            try {
                return \get_woocommerce_currency() === 'EUR';
            } catch (\Throwable $exception) {
                return \false;
            }
        };
    }, "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
        /** @var string $src */
        $src = $getStaticAssetUrl(WeroGatewayModule::PACKAGE_NAME, "images/wero-logo.svg");
        $icon = new Icon('wero-logo', $src, 'Wero logo');
        return new StaticIconProvider($icon);
    }), "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"]), "wero.request_modifier" => new Factory(['config.authorization_mode'], static function (string $authorizationMode) : WeroRequestModifier {
        return new WeroRequestModifier($authorizationMode);
    })];
};
