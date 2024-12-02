<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Environment;

use Syde\Vendor\Dhii\Validation\ValidatorInterface;
use Syde\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Psr\Container\ContainerInterface;
class EnvironmentModule implements ExecutableModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container): bool
    {
        /** @var ValidatorInterface $validator */
        $validator = $container->get('core.environment_validator');
        $environment = $container->get('core.wp_environment');
        $validator->validate($environment);
        return \true;
    }
}
