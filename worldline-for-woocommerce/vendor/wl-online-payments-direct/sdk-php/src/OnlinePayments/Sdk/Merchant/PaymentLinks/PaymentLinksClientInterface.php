<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\PaymentLinks;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentLinkRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentLinkResponse;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
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
