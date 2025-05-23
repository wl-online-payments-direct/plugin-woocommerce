<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Services;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CalculateSurchargeResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CurrencyConversionResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetIINDetailsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetPrivacyPolicyResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\TestConnection;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface ServicesClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/services/surchargecalculation - Surcharge Calculation
     *
     * @param CalculateSurchargeRequest $body
     * @param CallContext $callContext
     * @return CalculateSurchargeResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     */
    public function surchargeCalculation(CalculateSurchargeRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/services/dccrate - Get Dcc Rate Inquiry Api
     *
     * @param CurrencyConversionRequest $body
     * @param CallContext $callContext
     * @return CurrencyConversionResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     */
    public function getDccRateInquiry(CurrencyConversionRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/services/privacypolicy - Get Privacy Policy
     *
     * @param GetPrivacyPolicyParams $query
     * @param CallContext $callContext
     * @return GetPrivacyPolicyResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     */
    public function getPrivacyPolicy(GetPrivacyPolicyParams $query, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/services/testconnection - Test connection
     *
     * @param CallContext $callContext
     * @return TestConnection
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     */
    public function testConnection(CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/services/getIINdetails - Get IIN details
     *
     * @param GetIINDetailsRequest $body
     * @param CallContext $callContext
     * @return GetIINDetailsResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     */
    public function getIINDetails(GetIINDetailsRequest $body, CallContext $callContext = null);
}
