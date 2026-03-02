<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factory;
return new Factory([], static function () : array {
    return \array_merge(['enabled' => ['title' => \__('Enable/Disable', 'worldline-for-woocommerce'), 'type' => 'checkbox', 'label' => \__('Enable Bank Transfer by Worldline', 'worldline-for-woocommerce'), 'default' => 'no'], 'instant_payment' => ['title' => \__('Accept instant payment only for Bank Transfers', 'worldline-for-woocommerce'), 'type' => 'checkbox', 'label' => \__('Enable', 'worldline-for-woocommerce'), 'default' => 'yes', 'description' => \__('By enabling this option, you will only accept bank transfers from your customers where the payment is done instantly.', 'worldline-for-woocommerce'), 'desc_tip' => \true]]);
});
