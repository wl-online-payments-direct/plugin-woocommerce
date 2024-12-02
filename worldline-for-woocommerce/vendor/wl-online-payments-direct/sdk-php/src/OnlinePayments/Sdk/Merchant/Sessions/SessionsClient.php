<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Sessions;

use Syde\Vendor\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\SessionRequest;
use Syde\Vendor\OnlinePayments\Sdk\ResponseClassMap;
class SessionsClient extends ApiResource implements SessionsClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createSession(SessionRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\SessionResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/sessions'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
}
