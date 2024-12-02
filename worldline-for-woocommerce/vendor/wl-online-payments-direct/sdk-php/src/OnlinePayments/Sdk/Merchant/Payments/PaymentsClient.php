<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Payments;

use Syde\Vendor\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CancelPaymentRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CompletePaymentRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\RefundRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\SubsequentPaymentRequest;
use Syde\Vendor\OnlinePayments\Sdk\ResponseClassMap;
class PaymentsClient extends ApiResource implements PaymentsClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createPayment(CreatePaymentRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentResponse');
        $responseClassMap->setDefaultErrorResponseClassName('Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentErrorResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getPayment($paymentId, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}'), $this->getClientMetaInfo(), null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function completePayment($paymentId, CompletePaymentRequest $body, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\CompletePaymentResponse');
        $responseClassMap->setDefaultErrorResponseClassName('Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentErrorResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/complete'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function cancelPayment($paymentId, CancelPaymentRequest $body = null, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\CancelPaymentResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/cancel'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function subsequentPayment($paymentId, SubsequentPaymentRequest $body, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\SubsequentPaymentResponse');
        $responseClassMap->setDefaultErrorResponseClassName('Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentErrorResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/subsequent'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function refundPayment($paymentId, RefundRequest $body, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\RefundResponse');
        $responseClassMap->setDefaultErrorResponseClassName('Syde\Vendor\OnlinePayments\Sdk\Domain\RefundErrorResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/refund'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function capturePayment($paymentId, CapturePaymentRequest $body, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\CaptureResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/capture'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getCaptures($paymentId, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\CapturesResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/captures'), $this->getClientMetaInfo(), null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getPaymentDetails($paymentId, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentDetailsResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/details'), $this->getClientMetaInfo(), null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getRefunds($paymentId, CallContext $callContext = null)
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\RefundsResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/refunds'), $this->getClientMetaInfo(), null, $callContext);
    }
}
