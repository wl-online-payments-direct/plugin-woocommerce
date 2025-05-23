<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Services;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ResponseClassMap;
class ServicesClient extends ApiResource implements ServicesClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function surchargeCalculation(CalculateSurchargeRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/surchargecalculation'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getDccRateInquiry(CurrencyConversionRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/dccrate'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getPrivacyPolicy(GetPrivacyPolicyParams $query, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetPrivacyPolicyResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/privacypolicy'), $this->getClientMetaInfo(), $query, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function testConnection(CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\TestConnection');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/testconnection'), $this->getClientMetaInfo(), null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getIINDetails(GetIINDetailsRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/services/getIINdetails'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
}
