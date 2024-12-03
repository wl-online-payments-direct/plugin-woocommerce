<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Transformer;

use Syde\Vendor\Dhii\Container\ServiceProvider;
use Syde\Vendor\Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Syde\Vendor\Dhii\Modular\Module\ModuleInterface;
use Syde\Vendor\Interop\Container\ServiceProviderInterface;
use Syde\Vendor\Psr\Container\ContainerInterface;
//phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
class BuilderModule implements ModuleInterface
{
    public function setup(): ServiceProviderInterface
    {
        return new ServiceProvider(['inpsyde.transformer' => function (C $ctr): Transformer {
            return new ConfigurableTransformer();
        }], []);
    }
    public function run(ContainerInterface $ctr)
    {
        // TODO: Implement run() method.
    }
}
