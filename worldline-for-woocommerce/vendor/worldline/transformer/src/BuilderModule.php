<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Transformer;

use Worldline\Vendor\Dhii\Container\ServiceProvider;
use Worldline\Vendor\Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Worldline\Vendor\Dhii\Modular\Module\ModuleInterface;
use Worldline\Vendor\Interop\Container\ServiceProviderInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
//phpcs:disable Worldline.CodeQuality.NoAccessors.NoGetter
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
