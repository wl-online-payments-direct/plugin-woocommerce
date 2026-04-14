<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Admin\WeroRefundReasonUi;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
class WeroGatewayModule implements ServiceModule, ExtendingModule, ExecutableModule
{
    use ModuleClassNameIdTrait;
    public const PACKAGE_NAME = 'worldline-wero-gateway';
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
    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $container) : bool
    {
        if (\is_admin()) {
            $refundReasonUi = new WeroRefundReasonUi();
            $refundReasonUi->register();
        }
        return \true;
    }
}
