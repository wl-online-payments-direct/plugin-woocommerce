<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\Payments;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\DeclinedPaymentException;
use Worldline\Vendor\OnlinePayments\Sdk\DeclinedRefundException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CancelPaymentRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CancelPaymentResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CaptureResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PaymentResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RefundRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RefundResponse;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
/**
 * Payments client interface.
 */
interface PaymentsClientInterface
{
    /**
     * Resource /v2/{merchantId}/payments - Create payment
     *
     * @param CreatePaymentRequest $body
     * @param CallContext|null $callContext
     * @return CreatePaymentResponse
     *
     * @throws DeclinedPaymentException
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function createPayment(CreatePaymentRequest $body, ?CallContext $callContext = null) : CreatePaymentResponse;
    /**
     * Resource /v2/{merchantId}/payments/{paymentId} - Get payment
     *
     * @param string $paymentId
     * @param CallContext|null $callContext
     * @return PaymentResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getPayment(string $paymentId, ?CallContext $callContext = null) : PaymentResponse;
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/details - Get payment details
     *
     * @param string $paymentId
     * @param CallContext|null $callContext
     * @return PaymentDetailsResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getPaymentDetails(string $paymentId, ?CallContext $callContext = null) : PaymentDetailsResponse;
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/cancel - Cancel payment
     *
     * @param string $paymentId
     * @param CancelPaymentRequest $body
     * @param CallContext|null $callContext
     * @return CancelPaymentResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function cancelPayment(string $paymentId, CancelPaymentRequest $body, ?CallContext $callContext = null) : CancelPaymentResponse;
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/capture - Capture payment
     *
     * @param string $paymentId
     * @param CapturePaymentRequest $body
     * @param CallContext|null $callContext
     * @return CaptureResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function capturePayment(string $paymentId, CapturePaymentRequest $body, ?CallContext $callContext = null) : CaptureResponse;
    /**
     * Resource /v2/{merchantId}/payments/{paymentId}/refund - Refund payment
     *
     * @param string $paymentId
     * @param RefundRequest $body
     * @param CallContext|null $callContext
     * @return RefundResponse
     *
     * @throws DeclinedRefundException
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function refundPayment(string $paymentId, RefundRequest $body, ?CallContext $callContext = null) : RefundResponse;
}
