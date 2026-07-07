<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\Payouts;

use Worldline\Vendor\OnlinePayments\Sdk\ApiResource;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreatePayoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PayoutResponse;
use Worldline\Vendor\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Payouts client.
 */
class PayoutsClient extends ApiResource implements PayoutsClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function createPayout(CreatePayoutRequest $body, ?CallContext $callContext = null) : PayoutResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\PayoutResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\PayoutErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payouts'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getPayout(string $payoutId, ?CallContext $callContext = null) : PayoutResponse
    {
        $this->context['payoutId'] = $payoutId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\PayoutResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payouts/{payoutId}'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /** @return ExceptionFactory */
    private function getResponseExceptionFactory() : ExceptionFactory
    {
        if (\is_null($this->responseExceptionFactory)) {
            $this->responseExceptionFactory = new ExceptionFactory();
        }
        return $this->responseExceptionFactory;
    }
}
