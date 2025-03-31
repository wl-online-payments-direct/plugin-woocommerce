<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payouts;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\DeclinedPayoutException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatePayoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PayoutResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface PayoutsClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/payouts - Create payout
     *
     * @param CreatePayoutRequest $body
     * @param CallContext $callContext
     * @return PayoutResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     * @throws DeclinedPayoutException
     */
    public function createPayout(CreatePayoutRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payouts/{payoutId} - Get payout
     *
     * @param string $payoutId
     * @param CallContext $callContext
     * @return PayoutResponse
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
    public function getPayout($payoutId, CallContext $callContext = null);
}
