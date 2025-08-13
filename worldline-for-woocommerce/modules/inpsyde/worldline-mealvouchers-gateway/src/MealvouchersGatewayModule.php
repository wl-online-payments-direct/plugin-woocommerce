<?php

declare(strict_types=1);

namespace Inpsyde\WorldlineForWoocommerce\MealvouchersGateway;

use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Inpsyde\Assets\Asset;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ExecutableModule;
use Inpsyde\Modularity\Module\ServiceModule;
use Inpsyde\Modularity\Module\ExtendingModule;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

class MealvouchersGatewayModule implements ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;

    public const PACKAGE_NAME = 'worldline-mealvouchers-gateway';

    public function services(): array
    {
        static $services;

        if ($services === null) {
            $services = require_once dirname(__DIR__) . '/inc/services.php';
        }

        return $services();
    }

    public function extensions(): array
    {
        static $extensions;

        if ($extensions === null) {
            $extensions = require_once dirname(__DIR__) . '/inc/extensions.php';
        }

        return $extensions();
    }
}
