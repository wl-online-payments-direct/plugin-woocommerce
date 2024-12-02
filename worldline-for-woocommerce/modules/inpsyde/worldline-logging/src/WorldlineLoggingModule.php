<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlineLogging;

use Syde\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Psr\Container\ContainerInterface;
class WorldlineLoggingModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    /**
     * @param ContainerInterface $container
     * @return bool
     */
    public function run(ContainerInterface $container): bool
    {
        return \true;
    }
    public function services(): array
    {
        static $services;
        if ($services === null) {
            $services = require_once dirname(__DIR__) . '/inc/services.php';
        }
        return $services();
    }
    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = require_once dirname(__DIR__) . '/inc/extensions.php';
        }
        return $extensions();
    }
}
