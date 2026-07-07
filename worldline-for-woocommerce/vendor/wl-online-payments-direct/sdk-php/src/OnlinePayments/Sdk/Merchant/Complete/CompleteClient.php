<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\Complete;

use Worldline\Vendor\OnlinePayments\Sdk\ApiResource;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CompletePaymentRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CompletePaymentResponse;
use Worldline\Vendor\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Complete client.
 */
class CompleteClient extends ApiResource implements CompleteClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function completePayment(string $paymentId, CompletePaymentRequest $body, ?CallContext $callContext = null) : CompletePaymentResponse
    {
        $this->context['paymentId'] = $paymentId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\CompletePaymentResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Worldline\\Vendor\\OnlinePayments\\Sdk\\Domain\\PaymentErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payments/{paymentId}/complete'), $this->getClientMetaInfo(), $body, null, $callContext);
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
