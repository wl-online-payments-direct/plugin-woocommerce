<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokens;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateTokenRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatedTokenResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\TokenResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface TokensClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/tokens - Create token
     *
     * @param CreateTokenRequest $body
     * @param CallContext $callContext
     * @return CreatedTokenResponse
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
    public function createToken(CreateTokenRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/tokens/{tokenId} - Get token
     *
     * @param string $tokenId
     * @param CallContext $callContext
     * @return TokenResponse
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
    public function getToken($tokenId, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/tokens/{tokenId} - Delete token
     *
     * @param string $tokenId
     * @param CallContext $callContext
     * @return null
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
    public function deleteToken($tokenId, CallContext $callContext = null);
}
