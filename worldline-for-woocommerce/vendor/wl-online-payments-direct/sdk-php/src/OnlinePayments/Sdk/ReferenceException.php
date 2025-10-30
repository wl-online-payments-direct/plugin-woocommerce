<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\DataObject;
/**
 * Class ReferenceException
 *
 * @package OnlinePayments\Sdk
 */
class ReferenceException extends ResponseException
{
    /**
     * @param int $httpStatusCode
     * @param DataObject $response
     * @param string $message
     */
    public function __construct($httpStatusCode, DataObject $response, $message = null)
    {
        if (\is_null($message)) {
            $message = 'the payment platform returned a reference error response';
        }
        parent::__construct($httpStatusCode, $response, $message);
    }
}
