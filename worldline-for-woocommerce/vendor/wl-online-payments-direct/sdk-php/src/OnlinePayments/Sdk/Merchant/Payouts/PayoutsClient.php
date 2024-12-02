<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Payouts;

use Syde\Vendor\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreatePayoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\ResponseClassMap;
class PayoutsClient extends ApiResource implements PayoutsClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createPayout(CreatePayoutRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\PayoutResponse');
        $responseClassMap->setDefaultErrorResponseClassName('Syde\Vendor\OnlinePayments\Sdk\Domain\PayoutErrorResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payouts'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getPayout($payoutId, CallContext $callContext = null)
    {
        $this->context['payoutId'] = $payoutId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\PayoutResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/payouts/{payoutId}'), $this->getClientMetaInfo(), null, $callContext);
    }
}
