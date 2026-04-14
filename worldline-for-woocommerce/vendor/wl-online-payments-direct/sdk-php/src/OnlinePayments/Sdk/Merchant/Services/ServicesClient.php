<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Services;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\TestConnection;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Services client.
 */
class ServicesClient extends ApiResource implements ServicesClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function testConnection(?CallContext $callContext = null) : TestConnection
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\TestConnection';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/testconnection'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getIINDetails(GetIINDetailsRequest $body, ?CallContext $callContext = null) : GetIINDetailsResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\GetIINDetailsResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/getIINdetails'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getDccRateInquiry(CurrencyConversionRequest $body, ?CallContext $callContext = null) : CurrencyConversionResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\CurrencyConversionResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/dccrate'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function surchargeCalculation(CalculateSurchargeRequest $body, ?CallContext $callContext = null) : CalculateSurchargeResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\CalculateSurchargeResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/surchargecalculation'), $this->getClientMetaInfo(), $body, null, $callContext);
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
