<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedTokenization;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedTokenizationResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface HostedTokenizationClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/hostedtokenizations - Create hosted tokenization session
     *
     * @param CreateHostedTokenizationRequest $body
     * @param CallContext $callContext
     * @return CreateHostedTokenizationResponse
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
    public function createHostedTokenization(CreateHostedTokenizationRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/hostedtokenizations/{hostedTokenizationId} - Get hosted tokenization session
     *
     * @param string $hostedTokenizationId
     * @param CallContext $callContext
     * @return GetHostedTokenizationResponse
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
    public function getHostedTokenization($hostedTokenizationId, CallContext $callContext = null);
}
