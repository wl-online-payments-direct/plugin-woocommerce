<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedTokenization;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ResponseClassMap;
class HostedTokenizationClient extends ApiResource implements HostedTokenizationClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createHostedTokenization(CreateHostedTokenizationRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedTokenizationResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedtokenizations'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getHostedTokenization($hostedTokenizationId, CallContext $callContext = null)
    {
        $this->context['hostedTokenizationId'] = $hostedTokenizationId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/hostedtokenizations/{hostedTokenizationId}'), $this->getClientMetaInfo(), null, $callContext);
    }
}
