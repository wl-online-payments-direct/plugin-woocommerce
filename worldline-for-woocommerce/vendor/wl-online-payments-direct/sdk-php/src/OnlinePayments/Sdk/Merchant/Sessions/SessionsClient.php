<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Sessions;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SessionRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ResponseClassMap;
class SessionsClient extends ApiResource implements SessionsClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createSession(SessionRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SessionResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/sessions'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
}
