<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Environment;

use Worldline\Vendor\Dhii\Validation\ValidatorInterface;
use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Psr\Container\ContainerInterface;
class EnvironmentModule implements ExecutableModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container) : bool
    {
        /** @var ValidatorInterface $validator */
        $validator = $container->get('core.environment_validator');
        $environment = $container->get('core.wp_environment');
        $validator->validate($environment);
        return \true;
    }
}
