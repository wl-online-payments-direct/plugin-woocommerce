<?php

declare(strict_types=1);

namespace Inpsyde\WorldlineForWoocommerce\EpsGateway;

use Inpsyde\Modularity\Module\ExtendingModule;
use Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Inpsyde\Modularity\Module\ServiceModule;

class EpsGatewayModule implements ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;

    public const PACKAGE_NAME = 'worldline-eps-gateway';

    public function services(): array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
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
            $extensions = (require_once \dirname(__DIR__) . '/inc/extensions.php');
        }

        return $extensions();
    }
}
