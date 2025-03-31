<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\ProductGroups;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ResponseClassMap;
class ProductGroupsClient extends ApiResource implements ProductGroupsClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function getProductGroups(GetProductGroupsParams $query, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetPaymentProductGroupsResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/productgroups'), $this->getClientMetaInfo(), $query, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getProductGroup($paymentProductGroupId, GetProductGroupParams $query, CallContext $callContext = null)
    {
        $this->context['paymentProductGroupId'] = $paymentProductGroupId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProductGroup');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/productgroups/{paymentProductGroupId}'), $this->getClientMetaInfo(), $query, $callContext);
    }
}
