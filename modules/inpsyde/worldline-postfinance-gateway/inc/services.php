<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PostfinanceGateway\Payment\PostfinanceRequestModifier;
return static function () : array {
    return ["postfinance.request_modifier" => new Constructor(PostfinanceRequestModifier::class, [])];
};
