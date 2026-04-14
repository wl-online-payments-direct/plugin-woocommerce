<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokenization;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateCertificateResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CsrRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\DetokenizationResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
/**
 * Tokenization client interface.
 */
interface TokenizationClientInterface
{
    /**
     * Resource /v2/{merchantId}/detokenize/csr - Sign certificate
     *
     * @param CsrRequest $body
     * @param CallContext|null $callContext
     * @return CreateCertificateResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function createCertificate(CsrRequest $body, ?CallContext $callContext = null) : CreateCertificateResponse;
    /**
     * Resource /v2/{merchantId}/detokenize/tokens - Get sensitive card details by card alias tokens
     *
     * @param GetCardDataByTokensParams $query
     * @param CallContext|null $callContext
     * @return DetokenizationResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getCardDataByTokens(GetCardDataByTokensParams $query, ?CallContext $callContext = null) : DetokenizationResponse;
    /**
     * Resource /v2/{merchantId}/detokenize/payments - Get sensitive card details by card payment identifiers
     *
     * @param GetCardDataByPaymentsParams $query
     * @param CallContext|null $callContext
     * @return DetokenizationResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getCardDataByPayments(GetCardDataByPaymentsParams $query, ?CallContext $callContext = null) : DetokenizationResponse;
}
