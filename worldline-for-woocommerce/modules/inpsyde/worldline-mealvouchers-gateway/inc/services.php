<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\DefaultIconsRenderer;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Icon;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\IconProviderInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\StaticIconProvider;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\MealvouchersGateway\MealvouchersGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\MealvouchersGateway\Payment\MealvouchersRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function () : array {
    $moduleRoot = \dirname(__FILE__, 2);
    $gatewayId = GatewayIds::MEALVOUCHERS;
    return ["payment_gateway.{$gatewayId}.form_fields" => Service::fromFile("{$moduleRoot}/inc/fields.php"), "payment_gateway.{$gatewayId}.method_title" => static fn(): string => \__('Mealvouchers (Worldline)', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.title" => static fn() => \__('Mealvouchers', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.method_description" => static fn(): string => \__('Accept payments using Mealvouchers.', 'worldline-for-woocommerce'), "payment_gateway.{$gatewayId}.description" => static fn(): string => '', "payment_gateway.{$gatewayId}.order_button_text" => static fn(): ?string => null, "payment_gateway.{$gatewayId}.availability_callback" => static function (ContainerInterface $container) : callable {
        return static function () use($container) : bool {
            try {
                $settings = \get_option('woocommerce_worldline-for-woocommerce_settings', []);
                if (empty($settings['send_shopping_cart']) || $settings['send_shopping_cart'] !== 'yes') {
                    return \false;
                }
                $wcOrder = null;
                if (\is_wc_endpoint_url('order-pay')) {
                    global $wp;
                    if (isset($wp->query_vars['order-pay']) && \is_numeric($wp->query_vars['order-pay'])) {
                        $wcOrder = \wc_get_order(\absint($wp->query_vars['order-pay']));
                    }
                }
                // When order is created
                if ($wcOrder instanceof \WC_Order) {
                    $items = $wcOrder->get_items();
                    $customer_email = $wcOrder->get_billing_email();
                    $customer_id = $wcOrder->get_customer_id();
                }
                // When order is not yet created
                if (!$wcOrder instanceof \WC_Order) {
                    if (!\WC()->cart || \count(\WC()->cart->get_cart()) === 0) {
                        return \false;
                    }
                    $items = \WC()->cart->get_cart();
                    $customer_email = \WC()->customer ? \WC()->customer->get_email() : '';
                    $customer_id = \WC()->customer ? \WC()->customer->get_id() : 0;
                }
                global $wpdb;
                $table = $wpdb->prefix . 'product_type';
                foreach ($items as $item) {
                    $product_id = $item instanceof \WC_Order_Item_Product ? $item->get_product_id() : $item['product_id'] ?? 0;
                    $type = $wpdb->get_var($wpdb->prepare("SELECT `type` FROM `{$table}` WHERE product_id = %d", \absint($product_id)));
                    if (\in_array($type, ['food', 'home', 'gift'], \true)) {
                        return !empty($customer_email) && !empty($customer_id);
                    }
                }
                return \false;
            } catch (\Throwable $exception) {
                return \false;
            }
        };
    }, "payment_gateway.{$gatewayId}.payment_request_validator" => new Alias('payment_gateways.noop_payment_request_validator'), "payment_gateway.{$gatewayId}.payment_processor" => new Factory(['worldline_payment_gateway.hosted_checkout_url_factory', 'worldline_payment_gateway.wc_order_factory', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'worldline_payment_gateway.hosted_checkout_language', 'mealvouchers.request_modifier'], static fn(HostedCheckoutUrlFactory $urlFactory, WcOrderBasedOrderFactoryInterface $orderFactory, WcTokenRepository $vaultedTokenRepository, ?string $languageResolver, MealvouchersRequestModifier $mealvoucherRequestModifier): HostedPaymentProcessor => new HostedPaymentProcessor($urlFactory, $orderFactory, $vaultedTokenRepository, $languageResolver, $mealvoucherRequestModifier)), "payment_gateway.{$gatewayId}.supports" => static function () : array {
        return ['products', 'refunds'];
    }, "payment_gateway.{$gatewayId}.refund_processor" => new Alias('payment_gateway.' . GatewayIds::HOSTED_CHECKOUT . '.refund_processor'), "mealvouchers.request_modifier" => new Constructor(MealvouchersRequestModifier::class, []), "payment_gateway.{$gatewayId}.method_icon_provider" => new Factory(['assets.get_module_static_asset_url'], static function (callable $getStaticAssetUrl) : IconProviderInterface {
        /** @var string $src */
        $src = $getStaticAssetUrl(MealvouchersGatewayModule::PACKAGE_NAME, "images/mealvouchers-logo.svg");
        $icon = new Icon('mealvouchers-logo', $src, 'Mealvouchers logo');
        return new StaticIconProvider($icon);
    }), "payment_gateway.{$gatewayId}.gateway_icons_renderer" => new Constructor(DefaultIconsRenderer::class, ["payment_gateway.{$gatewayId}.method_icon_provider"])];
};
