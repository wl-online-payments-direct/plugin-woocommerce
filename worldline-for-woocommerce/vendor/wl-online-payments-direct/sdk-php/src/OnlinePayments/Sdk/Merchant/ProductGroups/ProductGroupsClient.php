<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\ProductGroups;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetPaymentProductGroupsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProductGroup;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ExceptionFactory;
/**
 * ProductGroups client.
 */
class ProductGroupsClient extends ApiResource implements ProductGroupsClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function getProductGroups(GetProductGroupsParams $query, ?CallContext $callContext = null) : GetPaymentProductGroupsResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\GetPaymentProductGroupsResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/productgroups'), $this->getClientMetaInfo(), $query, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getProductGroup(string $paymentProductGroupId, GetProductGroupParams $query, ?CallContext $callContext = null) : PaymentProductGroup
    {
        $this->context['paymentProductGroupId'] = $paymentProductGroupId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\PaymentProductGroup';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/productgroups/{paymentProductGroupId}'), $this->getClientMetaInfo(), $query, $callContext);
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
