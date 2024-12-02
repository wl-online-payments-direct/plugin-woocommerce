<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factory;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\PayOrderRedirectAction;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPage;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\StatusActionInterface;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\StatusCheckerInterface;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\WcOrderStatusChecker;
use Syde\Vendor\Psr\Container\ContainerInterface;
return static function (): array {
    return ['return_page.wc' => new Factory([], static function (): \WooCommerce {
        if (!\did_action('woocommerce_init')) {
            throw new \RuntimeException('"wc" service was accessed before the "woocommerce_init" hook');
        }
        return \WC();
    }), 'return_page.is_order_received_page' => new Factory(['return_page.wc'], static function (): bool {
        return is_order_received_page();
    }), 'return_page.payment_gateways' => static function (): array {
        return [];
    }, 'return_page.pages' => static function (ContainerInterface $container): array {
        $paymentGateways = $container->get('return_page.payment_gateways');
        $pages = [];
        /** @var string $paymentGatewayId */
        foreach ($paymentGateways as $paymentGatewayId) {
            $pages[$paymentGatewayId] = new ReturnPage($paymentGatewayId, $container);
        }
        return $pages;
    }, 'return_page.assets.handle' => static function (): string {
        return 'return-page';
    }, 'return_page.status_checker.wc_status' => static function (): StatusCheckerInterface {
        return new WcOrderStatusChecker();
    }, 'return_page.action.pay_order_redirect' => static function (): StatusActionInterface {
        return new PayOrderRedirectAction();
    }];
};
