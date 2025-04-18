<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

/**
 * Class SensitiveValueObfuscator
 *
 * @package OnlinePayments\Sdk
 */
class SensitiveValueObfuscator
{
    /**
     * @param string $value
     * @return string
     */
    public function obfuscate($value)
    {
        return empty($value) ? $value : '***';
    }
}
