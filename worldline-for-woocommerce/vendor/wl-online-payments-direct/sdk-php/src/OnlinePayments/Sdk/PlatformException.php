<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\DataObject;
/**
 * Class PlatformException
 *
 * @package OnlinePayments\Sdk
 */
class PlatformException extends ApiException
{
    /**
     * @param int $httpStatusCode
     * @param DataObject $response
     * @param string|null $message
     */
    public function __construct(int $httpStatusCode, DataObject $response, ?string $message = null)
    {
        if (\is_null($message)) {
            $message = 'the payment platform returned an error response';
        }
        parent::__construct($httpStatusCode, $response, $message);
    }
}
