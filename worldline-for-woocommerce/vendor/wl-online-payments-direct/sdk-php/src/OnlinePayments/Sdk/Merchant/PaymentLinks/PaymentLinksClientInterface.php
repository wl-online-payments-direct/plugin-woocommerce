<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\PaymentLinks;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatePaymentLinkRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentLinkResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface PaymentLinksClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/paymentlinks - Create payment link
     *
     * @param CreatePaymentLinkRequest $body
     * @param CallContext $callContext
     * @return PaymentLinkResponse
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
    public function createPaymentLink(CreatePaymentLinkRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/paymentlinks/{paymentLinkId} - Get payment link by ID
     *
     * @param string $paymentLinkId
     * @param CallContext $callContext
     * @return PaymentLinkResponse
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
    public function getPaymentLinkById($paymentLinkId, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/paymentlinks/{paymentLinkId}/cancel - Cancel PaymentLink by ID
     *
     * @param string $paymentLinkId
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
    public function cancelPaymentLinkById($paymentLinkId, CallContext $callContext = null);
}
