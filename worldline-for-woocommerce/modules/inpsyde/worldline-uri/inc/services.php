<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factories\Alias;
use Syde\Vendor\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Uri\UriFactory;
return static function (): array {
    return ['uri.factory' => new Constructor(UriFactory::class, []), 'uri.builder' => new Alias('uri.factory')];
};
