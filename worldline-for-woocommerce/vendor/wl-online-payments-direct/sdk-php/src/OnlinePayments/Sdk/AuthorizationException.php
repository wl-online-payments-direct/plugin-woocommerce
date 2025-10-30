<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\DataObject;
/**
 * Class AuthorizationException
 *
 * @package OnlinePayments\Sdk
 */
class AuthorizationException extends ResponseException
{
    /**
     * @param int $httpStatusCode
     * @param DataObject $response
     * @param string $message
     */
    public function __construct($httpStatusCode, DataObject $response, $message = null)
    {
        if (\is_null($message)) {
            $message = 'the payment platform returned an authorization error response';
        }
        parent::__construct($httpStatusCode, $response, $message);
    }
}
