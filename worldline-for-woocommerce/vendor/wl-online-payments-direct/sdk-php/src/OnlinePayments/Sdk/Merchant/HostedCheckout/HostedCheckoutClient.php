<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedCheckout;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ResponseClassMap;
class HostedCheckoutClient extends ApiResource implements HostedCheckoutClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createHostedCheckout(CreateHostedCheckoutRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedcheckouts'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getHostedCheckout($hostedCheckoutId, CallContext $callContext = null)
    {
        $this->context['hostedCheckoutId'] = $hostedCheckoutId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetHostedCheckoutResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedcheckouts/{hostedCheckoutId}'), $this->getClientMetaInfo(), null, $callContext);
    }
}
