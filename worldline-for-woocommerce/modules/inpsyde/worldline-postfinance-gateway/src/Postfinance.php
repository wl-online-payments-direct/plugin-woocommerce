<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PostfinanceGateway;

use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\DefaultIconsRenderer;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\GatewayIconsRendererInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Icon;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\IconProviderInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Method\DefaultPaymentMethodDefinitionTrait;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Method\PaymentMethodDefinition;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentRequestValidatorInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\RefundProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\StaticIconProvider;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PostfinanceGateway\Payment\PostfinanceRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
class Postfinance implements PaymentMethodDefinition
{
    use DefaultPaymentMethodDefinitionTrait;
    public function id() : string
    {
        return GatewayIds::POSTFINANCE;
    }
    public function orderButtonText(ContainerInterface $container) : string
    {
        return '';
    }
    public function paymentProcessor(ContainerInterface $container) : PaymentProcessorInterface
    {
        $paymentProcessor = new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'postfinance.request_modifier'], static function (HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage, PostfinanceRequestModifier $postfinanceRequestModifier) : HostedPaymentProcessor {
            return new HostedPaymentProcessor($hostedCheckoutUrlFactory, $wcOrderBasedOrderFactory, $wcTokenRepository, $hostedCheckoutLanguage, $postfinanceRequestModifier);
        });
        return $paymentProcessor($container);
    }
    public function paymentRequestValidator(ContainerInterface $container) : PaymentRequestValidatorInterface
    {
        $paymentRequestValidator = new Alias('payment_gateways.noop_payment_request_validator');
        return $paymentRequestValidator($container);
    }
    public function title(ContainerInterface $container) : string
    {
        return \__('PostFinance', 'worldline-for-woocommerce');
    }
    public function methodTitle(ContainerInterface $container) : string
    {
        return \__('PostFinance (Worldline)', 'worldline-for-woocommerce');
    }
    public function description(ContainerInterface $container) : string
    {
        return '';
    }
    public function methodDescription(ContainerInterface $container) : string
    {
        return \__('Accept payments with PostFinance.', 'worldline-for-woocommerce');
    }
    public function availabilityCallback(ContainerInterface $container) : callable
    {
        return static function () : bool {
            $currency = \get_woocommerce_currency();
            return \in_array($currency, ['EUR', 'CHF'], \true);
        };
    }
    public function supports(ContainerInterface $container) : array
    {
        return ['products', 'refunds'];
    }
    public function refundProcessor(ContainerInterface $container) : RefundProcessorInterface
    {
        $refundProcessor = new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor');
        return $refundProcessor($container);
    }
    public function paymentMethodIconProvider(ContainerInterface $container) : IconProviderInterface
    {
        $iconProvider = new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
            /** @var string $src */
            $src = $getStaticAssetUrl(PostfinanceGatewayModule::PACKAGE_NAME, "images/postfinance-logo.svg");
            $icon = new Icon('postfinance-logo', $src, 'Postfinance logo');
            return new StaticIconProvider($icon);
        });
        return $iconProvider($container);
    }
    public function gatewayIconsRenderer(ContainerInterface $container) : GatewayIconsRendererInterface
    {
        $gatewayId = $this->id();
        $iconsRenderer = new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"]);
        return $iconsRenderer($container);
    }
    public function formFields(ContainerInterface $container) : array
    {
        return ['enabled' => ['title' => \__('Enable/Disable', 'worldline-for-woocommerce'), 'type' => 'checkbox', 'label' => \__('Enable PostFinance (Worldline)', 'worldline-for-woocommerce'), 'default' => 'no']];
    }
    public function icon(ContainerInterface $container) : string
    {
        return '';
    }
}
