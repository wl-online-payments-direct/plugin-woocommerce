<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Method;

use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\SettingsFieldRendererInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\SettingsFieldSanitizerInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
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
