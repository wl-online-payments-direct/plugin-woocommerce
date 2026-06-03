<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factory;
return new Factory([], static function () : array {
    return \array_merge(['enabled' => ['title' => \__('Enable/Disable', 'worldline-for-woocommerce'), 'type' => 'checkbox', 'label' => \__('Enable Sofinco (Worldline)', 'worldline-for-woocommerce'), 'default' => 'no'], 'title' => ['title' => \__('Title', 'worldline-for-woocommerce'), 'type' => 'text', 'description' => \__('Personalize the payment method title on the checkout page.', 'worldline-for-woocommerce'), 'desc_tip' => \__('If left empty, the default payment method name will be displayed on the checkout page.', 'worldline-for-woocommerce'), 'placeholder' => \__('Sofinco', 'worldline-for-woocommerce')]]);
});
