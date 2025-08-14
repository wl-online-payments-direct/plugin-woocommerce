<?php

declare(strict_types=1);

namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PayPalGateway;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;

class PayPalGatewayModule implements ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;

    public const PACKAGE_NAME = 'worldline-paypal-gateway';

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
