<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedFields;

use Worldline\Vendor\OnlinePayments\Sdk\ApiResource;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedFieldsSessionRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedFieldsSessionResponse;
use Worldline\Vendor\OnlinePayments\Sdk\ExceptionFactory;
/**
 * HostedFields client.
 */
class HostedFieldsClient extends ApiResource implements HostedFieldsClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function createHostedFieldsSession(CreateHostedFieldsSessionRequest $body, ?CallContext $callContext = null) : CreateHostedFieldsSessionResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\CreateHostedFieldsSessionResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedfields/sessions'), $this->getClientMetaInfo(), $body, null, $callContext);
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
