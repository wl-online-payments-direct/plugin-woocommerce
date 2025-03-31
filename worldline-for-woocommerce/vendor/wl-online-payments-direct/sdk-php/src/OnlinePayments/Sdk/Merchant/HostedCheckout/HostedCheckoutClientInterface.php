<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedCheckout;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
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
