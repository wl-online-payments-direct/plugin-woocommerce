<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Tokens;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateTokenRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreatedTokenResponse;
use Syde\Vendor\OnlinePayments\Sdk\Domain\TokenResponse;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
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
