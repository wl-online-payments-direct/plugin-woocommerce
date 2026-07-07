<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\GooglePayGateway;

use Worldline\Assets\AssetManager;
use Worldline\Assets\Script;
use Worldline\Assets\Style;
use Worldline\Assets\Asset;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\Modularity\Module\ExtendingModule;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentGateway;
use Worldline\Vendor\Psr\Container\ContainerExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Container\NotFoundExceptionInterface;
class GooglePayGatewayModule implements ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public const PACKAGE_NAME = 'worldline-google-pay-gateway';
    public function services() : array
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
    public function extensions() : array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = (require_once \dirname(__DIR__) . '/inc/extensions.php');
        }
        return $extensions();
    }
}
