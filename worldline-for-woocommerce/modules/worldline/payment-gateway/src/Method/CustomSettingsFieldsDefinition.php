<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\PaymentGateway\Method;

use Worldline\Vendor\Worldline\PaymentGateway\SettingsFieldRendererInterface;
use Worldline\Vendor\Worldline\PaymentGateway\SettingsFieldSanitizerInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
interface CustomSettingsFieldsDefinition
{
    /**
     * @return array<callable(ContainerInterface):SettingsFieldRendererInterface>
     */
    public function renderers() : array;
    /**
     * @return array<callable(ContainerInterface):SettingsFieldSanitizerInterface>
     */
    public function sanitizers() : array;
}
