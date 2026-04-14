<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payments;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CancelPaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CancelPaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CaptureResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatePaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Payments client.
 */
class PaymentsClient extends ApiResource implements PaymentsClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function createPayment(CreatePaymentRequest $body, ?CallContext $callContext = null) : CreatePaymentResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\CreatePaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\PaymentErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getPayment(string $paymentId, ?CallContext $callContext = null) : PaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\PaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getPaymentDetails(string $paymentId, ?CallContext $callContext = null) : PaymentDetailsResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\PaymentDetailsResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/details'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function cancelPayment(string $paymentId, CancelPaymentRequest $body, ?CallContext $callContext = null) : CancelPaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\CancelPaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/cancel'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function capturePayment(string $paymentId, CapturePaymentRequest $body, ?CallContext $callContext = null) : CaptureResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\CaptureResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/capture'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function refundPayment(string $paymentId, RefundRequest $body, ?CallContext $callContext = null) : RefundResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\RefundResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\RefundErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/refund'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /** @return ExceptionFactory */
    private function getResponseExceptionFactory() : ExceptionFactory
    {
        if (\is_null($this->responseExceptionFactory)) {
            $this->responseExceptionFactory = new ExceptionFactory();
        }
        return $this->responseExceptionFactory;
    }
}
