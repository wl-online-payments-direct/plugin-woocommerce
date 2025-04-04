<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

/**
 * Class ConnectionResponse
 *
 * @package OnlinePayments\Sdk
 */
interface ConnectionResponse
{
    /**
     * @return int
     */
    public function getHttpStatusCode();
    /**
     * @return array
     */
    public function getHeaders();
    /**
     * @param string $name
     * @return mixed
     */
    public function getHeaderValue($name);
    /**
     * @return string
     */
    public function getBody();
}
