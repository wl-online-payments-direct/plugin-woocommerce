<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\HostedTokenization;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedTokenizationResponse;
use Syde\Vendor\OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
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
