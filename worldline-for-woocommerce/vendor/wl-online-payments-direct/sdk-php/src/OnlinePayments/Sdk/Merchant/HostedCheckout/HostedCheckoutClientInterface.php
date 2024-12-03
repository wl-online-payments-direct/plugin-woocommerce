<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\HostedCheckout;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Syde\Vendor\OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
interface HostedCheckoutClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/hostedcheckouts - Create hosted checkout
     *
     * @param CreateHostedCheckoutRequest $body
     * @param CallContext $callContext
     * @return CreateHostedCheckoutResponse
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
    public function createHostedCheckout(CreateHostedCheckoutRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/hostedcheckouts/{hostedCheckoutId} - Get hosted checkout status
     *
     * @param string $hostedCheckoutId
     * @param CallContext $callContext
     * @return GetHostedCheckoutResponse
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
    public function getHostedCheckout($hostedCheckoutId, CallContext $callContext = null);
}
