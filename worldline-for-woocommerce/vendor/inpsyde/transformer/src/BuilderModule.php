<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Transformer;

use Syde\Vendor\Worldline\Dhii\Container\ServiceProvider;
use Syde\Vendor\Worldline\Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Syde\Vendor\Worldline\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Worldline\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
//phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
class BuilderModule implements ModuleInterface
{
    public function setup() : ServiceProviderInterface
    {
        return new ServiceProvider(['inpsyde.transformer' => static function (C $ctr) : Transformer {
            return new ConfigurableTransformer();
        }], []);
    }
    public function run(ContainerInterface $ctr)
    {
        // TODO: Implement run() method.
    }
}
