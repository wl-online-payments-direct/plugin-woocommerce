<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\Payouts;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\DeclinedPayoutException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreatePayoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PayoutResponse;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
/**
 * Payouts client interface.
 */
interface PayoutsClientInterface
{
    /**
     * Resource /v2/{merchantId}/payouts - Create payout
     *
     * @param CreatePayoutRequest $body
     * @param CallContext|null $callContext
     * @return PayoutResponse
     *
     * @throws DeclinedPayoutException
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function createPayout(CreatePayoutRequest $body, ?CallContext $callContext = null) : PayoutResponse;
    /**
     * Resource /v2/{merchantId}/payouts/{payoutId} - Get payout
     *
     * @param string $payoutId
     * @param CallContext|null $callContext
     * @return PayoutResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getPayout(string $payoutId, ?CallContext $callContext = null) : PayoutResponse;
}
