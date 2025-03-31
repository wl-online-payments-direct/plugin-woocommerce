<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payments;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\DeclinedPaymentException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\DeclinedRefundException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CancelPaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CancelPaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CaptureResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CapturesResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CompletePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CompletePaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatePaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SubsequentPaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SubsequentPaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface PaymentsClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/payments - Create payment
     *
     * @param CreatePaymentRequest $body
     * @param CallContext $callContext
     * @return CreatePaymentResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     * @throws DeclinedPaymentException
     */
    public function createPayment(CreatePaymentRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId} - Get payment
     *
     * @param string $paymentId
     * @param CallContext $callContext
     * @return PaymentResponse
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
    public function getPayment($paymentId, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/complete - Complete payment
     *
     * @param string $paymentId
     * @param CompletePaymentRequest $body
     * @param CallContext $callContext
     * @return CompletePaymentResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     * @throws DeclinedPaymentException
     */
    public function completePayment($paymentId, CompletePaymentRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/cancel - Cancel payment
     *
     * @param string $paymentId
     * @param CancelPaymentRequest $body
     * @param CallContext $callContext
     * @return CancelPaymentResponse
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
    public function cancelPayment($paymentId, CancelPaymentRequest $body = null, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/subsequent - Subsequent payment
     *
     * @param string $paymentId
     * @param SubsequentPaymentRequest $body
     * @param CallContext $callContext
     * @return SubsequentPaymentResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     * @throws DeclinedPaymentException
     */
    public function subsequentPayment($paymentId, SubsequentPaymentRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/refund - Refund payment
     *
     * @param string $paymentId
     * @param RefundRequest $body
     * @param CallContext $callContext
     * @return RefundResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     * @throws DeclinedRefundException
     */
    public function refundPayment($paymentId, RefundRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/capture - Capture payment
     *
     * @param string $paymentId
     * @param CapturePaymentRequest $body
     * @param CallContext $callContext
     * @return CaptureResponse
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
    public function capturePayment($paymentId, CapturePaymentRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/captures - Get Captures Api
     *
     * @param string $paymentId
     * @param CallContext $callContext
     * @return CapturesResponse
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
    public function getCaptures($paymentId, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/details - Get payment details
     *
     * @param string $paymentId
     * @param CallContext $callContext
     * @return PaymentDetailsResponse
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
    public function getPaymentDetails($paymentId, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/payments/{paymentId}/refunds - Get Refunds Api
     *
     * @param string $paymentId
     * @param CallContext $callContext
     * @return RefundsResponse
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
    public function getRefunds($paymentId, CallContext $callContext = null);
}
