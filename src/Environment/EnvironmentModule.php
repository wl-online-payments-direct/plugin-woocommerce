<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment;

use Syde\Vendor\Worldline\Dhii\Validation\ValidatorInterface;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
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
