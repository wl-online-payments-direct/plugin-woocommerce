<?php

declare (strict_types=1);
namespace Worldline\Vendor;

// phpcs:disable Worldline.CodeQuality.LineLength.TooLong
use Worldline\Vendor\Dhii\Services\Factories\Constructor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\PostfinanceGateway\Payment\PostfinanceRequestModifier;
return static function () : array {
    return ["postfinance.request_modifier" => new Constructor(PostfinanceRequestModifier::class, [])];
};
