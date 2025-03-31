<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Mandates;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateMandateRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ResponseClassMap;
class MandatesClient extends ApiResource implements MandatesClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createMandate(CreateMandateRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateMandateResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/mandates'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getMandate($uniqueMandateReference, CallContext $callContext = null)
    {
        $this->context['uniqueMandateReference'] = $uniqueMandateReference;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetMandateResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/mandates/{uniqueMandateReference}'), $this->getClientMetaInfo(), null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function blockMandate($uniqueMandateReference, CallContext $callContext = null)
    {
        $this->context['uniqueMandateReference'] = $uniqueMandateReference;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetMandateResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/mandates/{uniqueMandateReference}/block'), $this->getClientMetaInfo(), null, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function unblockMandate($uniqueMandateReference, CallContext $callContext = null)
    {
        $this->context['uniqueMandateReference'] = $uniqueMandateReference;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetMandateResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/mandates/{uniqueMandateReference}/unblock'), $this->getClientMetaInfo(), null, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function revokeMandate($uniqueMandateReference, CallContext $callContext = null)
    {
        $this->context['uniqueMandateReference'] = $uniqueMandateReference;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetMandateResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/mandates/{uniqueMandateReference}/revoke'), $this->getClientMetaInfo(), null, null, $callContext);
    }
}
