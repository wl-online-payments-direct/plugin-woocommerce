<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

use Exception;
/**
 * Class MultipartDataObject
 *
 * @package OnlinePayments\Sdk
 */
abstract class MultipartDataObject
{
    /**
     * @return MultipartFormDataObject
     */
    abstract public function toMultipartFormDataObject();
    public function __set($name, $value)
    {
        throw new Exception('Cannot add new property ' . $name . ' to instances of class ' . get_class($this));
    }
}
