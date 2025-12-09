<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Value;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Checkout\ReturnPageStatusChecker;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Checkout\ReturnPageStatusUpdater;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPageRender;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
return static function () : array {
    $returnPageServices = [];
    foreach (GatewayIds::ALL as $gatewayId) {
        $returnPageServices = \array_merge($returnPageServices, ["return_page.{$gatewayId}.status_checker" => new Constructor(ReturnPageStatusChecker::class, []), "return_page.{$gatewayId}.status_updater" => new Constructor(ReturnPageStatusUpdater::class, ['worldline_payment_gateway.order_updater']), "return_page.{$gatewayId}.retry_count" => new Value(6), "return_page.{$gatewayId}.interval" => new Value(2000), "return_page.{$gatewayId}.action.loading" => static fn(): string => \__('Processing your payment. Please wait...', 'worldline-for-woocommerce'), "return_page.{$gatewayId}.message.status.pending" => static fn(): string => \__('We apologize for the delay. Your payment is still processing.', 'worldline-for-woocommerce'), "return_page.{$gatewayId}.action.status.cancelled" => new Alias('return_page.action.pay_order_redirect'), "return_page.{$gatewayId}.message.status.cancelled" => static fn(): string => \__('You cancelled the checkout.', 'worldline-for-woocommerce'), "return_page.{$gatewayId}.message.status.success" => static function () : string {
            return '';
        }, "return_page.{$gatewayId}.message.status.failed" => static function () : string {
            return '';
        }, "return_page.{$gatewayId}.message.render" => new Constructor(ReturnPageRender::class)]);
    }
    return \array_merge($returnPageServices, []);
};
