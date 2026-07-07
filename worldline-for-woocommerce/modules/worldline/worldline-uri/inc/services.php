<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Dhii\Services\Factories\Alias;
use Worldline\Vendor\Dhii\Services\Factories\Constructor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Uri\UriFactory;
return static function () : array {
    return ['uri.factory' => new Constructor(UriFactory::class, []), 'uri.builder' => new Alias('uri.factory')];
};
