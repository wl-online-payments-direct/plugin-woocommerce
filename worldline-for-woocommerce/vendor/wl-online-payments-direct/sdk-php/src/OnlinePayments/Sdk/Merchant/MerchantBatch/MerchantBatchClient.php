<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantBatch;

use Worldline\Vendor\OnlinePayments\Sdk\ApiResource;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\GetBatchStatusResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\SubmitBatchRequestBody;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\SubmitBatchResponse;
use Worldline\Vendor\OnlinePayments\Sdk\ExceptionFactory;
/**
 * MerchantBatch client.
 */
class MerchantBatchClient extends ApiResource implements MerchantBatchClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function submitBatch(SubmitBatchRequestBody $body, ?CallContext $callContext = null) : SubmitBatchResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\SubmitBatchResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            $callContext = $callContext ?? new CallContext();
            $callContext->setGzip(\true);
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/merchant-batches'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function processBatch(string $merchantBatchReference, ?CallContext $callContext = null) : void
    {
        $this->context['merchantBatchReference'] = $merchantBatchReference;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/merchant-batches/{merchantBatchReference}/process'), $this->getClientMetaInfo(), null, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getBatchStatus(string $merchantBatchReference, ?CallContext $callContext = null) : GetBatchStatusResponse
    {
        $this->context['merchantBatchReference'] = $merchantBatchReference;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\GetBatchStatusResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/merchant-batches/{merchantBatchReference}'), $this->getClientMetaInfo(), null, $callContext);
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
