<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uri\UriFactory;
return static function () : array {
    return ['uri.factory' => new Constructor(UriFactory::class, []), 'uri.builder' => new Alias('uri.factory')];
};
