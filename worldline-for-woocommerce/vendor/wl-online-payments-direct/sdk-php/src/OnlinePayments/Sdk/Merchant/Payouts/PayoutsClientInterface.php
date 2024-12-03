<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Payouts;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\DeclinedPayoutException;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreatePayoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PayoutResponse;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
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
