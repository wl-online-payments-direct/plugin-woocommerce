<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\Services;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CalculateSurchargeRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CalculateSurchargeResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CurrencyConversionRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CurrencyConversionResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\GetIINDetailsRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\GetIINDetailsResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\TestConnection;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
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
    function testConnection(?CallContext $callContext = null) : TestConnection;
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
    function getIINDetails(GetIINDetailsRequest $body, ?CallContext $callContext = null) : GetIINDetailsResponse;
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
    function getDccRateInquiry(CurrencyConversionRequest $body, ?CallContext $callContext = null) : CurrencyConversionResponse;
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
    function surchargeCalculation(CalculateSurchargeRequest $body, ?CallContext $callContext = null) : CalculateSurchargeResponse;
}
