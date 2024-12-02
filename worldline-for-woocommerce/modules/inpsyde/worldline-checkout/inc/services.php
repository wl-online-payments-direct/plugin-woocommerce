<?php

declare (strict_types=1);
namespace Syde\Vendor;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Dhii\Services\Factories\Alias;
use Syde\Vendor\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Dhii\Services\Factories\Value;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Checkout\ReturnPageStatusChecker;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Checkout\ReturnPageStatusUpdater;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPageRender;
return static function (): array {
    return ['return_page.worldline-for-woocommerce.status_checker' => new Constructor(ReturnPageStatusChecker::class, []), 'return_page.worldline-for-woocommerce.status_updater' => new Constructor(ReturnPageStatusUpdater::class, ['worldline_payment_gateway.order_updater']), 'return_page.worldline-for-woocommerce.retry_count' => new Value(6), 'return_page.worldline-for-woocommerce.interval' => new Value(2000), 'return_page.worldline-for-woocommerce.action.loading' => static fn(): string => \__('Processing your payment. Please wait...', 'worldline-for-woocommerce'), 'return_page.worldline-for-woocommerce.message.status.pending' => static fn(): string => \__('We apologize for the delay. Your payment is still processing.', 'worldline-for-woocommerce'), 'return_page.worldline-for-woocommerce.action.status.cancelled' => new Alias('return_page.action.pay_order_redirect'), 'return_page.worldline-for-woocommerce.message.status.cancelled' => static fn(): string => \__('You cancelled the checkout.', 'worldline-for-woocommerce'), 'return_page.worldline-for-woocommerce.message.status.success' => static function (): string {
        return '';
    }, 'return_page.worldline-for-woocommerce.message.status.failed' => static function (): string {
        return '';
    }, 'return_page.worldline-for-woocommerce.message.render' => new Constructor(ReturnPageRender::class)];
};
