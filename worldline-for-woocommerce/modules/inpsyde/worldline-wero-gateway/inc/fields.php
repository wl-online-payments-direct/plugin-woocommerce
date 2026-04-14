<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factory;
return new Factory([], static function () : array {
    return ['enabled' => ['title' => \__('Enable/Disable', 'worldline-for-woocommerce'), 'type' => 'checkbox', 'label' => \__('Enable Wero (Worldline)', 'worldline-for-woocommerce'), 'default' => 'no']];
});
