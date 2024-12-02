<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\HostedCheckout;

use Syde\Vendor\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\ResponseClassMap;
class HostedCheckoutClient extends ApiResource implements HostedCheckoutClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createHostedCheckout(CreateHostedCheckoutRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedcheckouts'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getHostedCheckout($hostedCheckoutId, CallContext $callContext = null)
    {
        $this->context['hostedCheckoutId'] = $hostedCheckoutId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedcheckouts/{hostedCheckoutId}'), $this->getClientMetaInfo(), null, $callContext);
    }
}
