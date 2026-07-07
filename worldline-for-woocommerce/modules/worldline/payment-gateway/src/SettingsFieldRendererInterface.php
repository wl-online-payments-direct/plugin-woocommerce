<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\PaymentGateway;

interface SettingsFieldRendererInterface
{
    public function render(string $fieldId, array $fieldConfig, PaymentGateway $gateway) : string;
}
