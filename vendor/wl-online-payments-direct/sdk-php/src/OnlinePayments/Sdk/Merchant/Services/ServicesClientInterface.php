<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Services;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\TestConnection;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
/**
 * Services client interface.
 */
interface ServicesClientInterface
{
    /**
     * Resource /v2/{merchantId}/services/testconnection - Test connection
     *
     * @param CallContext|null $callContext
     * @return TestConnection
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function testConnection(CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/services/getIINdetails - Get IIN details
     *
     * @param GetIINDetailsRequest $body
     * @param CallContext|null $callContext
     * @return GetIINDetailsResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getIINDetails(GetIINDetailsRequest $body, CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/services/dccrate - Get currency conversion quote
     *
     * @param CurrencyConversionRequest $body
     * @param CallContext|null $callContext
     * @return CurrencyConversionResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getDccRateInquiry(CurrencyConversionRequest $body, CallContext $callContext = null);
    /**
     * Resource /v2/{merchantId}/services/surchargecalculation - Surcharge Calculation
     *
     * @param CalculateSurchargeRequest $body
     * @param CallContext|null $callContext
     * @return CalculateSurchargeResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function surchargeCalculation(CalculateSurchargeRequest $body, CallContext $callContext = null);
}
