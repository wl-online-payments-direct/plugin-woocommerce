<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\PostfinanceGateway;

use Worldline\Vendor\Worldline\Modularity\Module\ExtendingModule;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\PaymentGateway\Method\PaymentMethodDefinition;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentMethodServiceProviderTrait;
class PostfinanceGatewayModule implements ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    use PaymentMethodServiceProviderTrait;
    public const PACKAGE_NAME = 'worldline-postfinance-gateway';
    private PaymentMethodDefinition $paymentMethod;
    public function __construct()
    {
        $this->paymentMethod = new Postfinance();
    }
    public function services() : array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
        }
        return \array_merge($services(), $this->providePaymentMethodServices($this->paymentMethod));
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
