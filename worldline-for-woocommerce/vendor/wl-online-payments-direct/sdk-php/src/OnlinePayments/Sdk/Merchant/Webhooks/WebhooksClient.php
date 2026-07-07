<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\Webhooks;

use Worldline\Vendor\OnlinePayments\Sdk\ApiResource;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\SendTestRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ValidateCredentialsRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ValidateCredentialsResponse;
use Worldline\Vendor\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Webhooks client.
 */
class WebhooksClient extends ApiResource implements WebhooksClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function validateWebhookCredentials(ValidateCredentialsRequest $body, ?CallContext $callContext = null) : ValidateCredentialsResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ValidateCredentialsResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/webhooks/validateCredentials'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function sendTestWebhook(SendTestRequest $body, ?CallContext $callContext = null) : void
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/webhooks/sendtest'), $this->getClientMetaInfo(), $body, null, $callContext);
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
