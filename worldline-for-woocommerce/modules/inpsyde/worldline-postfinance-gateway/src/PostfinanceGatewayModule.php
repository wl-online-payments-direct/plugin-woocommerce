<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PostfinanceGateway;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Method\PaymentMethodDefinition;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentMethodServiceProviderTrait;
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
