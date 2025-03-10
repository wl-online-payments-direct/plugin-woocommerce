<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\PaymentGateway\Method;

use Syde\Vendor\Inpsyde\PaymentGateway\SettingsFieldRendererInterface;
use Syde\Vendor\Inpsyde\PaymentGateway\SettingsFieldSanitizerInterface;
use Syde\Vendor\Psr\Container\ContainerInterface;
interface CustomSettingsFieldsDefinition
{
    /**
     * @return array<callable(ContainerInterface):SettingsFieldRendererInterface>
     */
    public function renderers(): array;
    /**
     * @return array<callable(ContainerInterface):SettingsFieldSanitizerInterface>
     */
    public function sanitizers(): array;
}
