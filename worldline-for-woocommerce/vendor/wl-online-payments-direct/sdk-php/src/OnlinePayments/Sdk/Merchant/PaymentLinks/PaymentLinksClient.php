<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\PaymentLinks;

use Syde\Vendor\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentLinkRequest;
use Syde\Vendor\OnlinePayments\Sdk\ResponseClassMap;
class PaymentLinksClient extends ApiResource implements PaymentLinksClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createPaymentLink(CreatePaymentLinkRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentLinkResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/paymentlinks'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getPaymentLinkById($paymentLinkId, CallContext $callContext = null)
    {
        $this->context['paymentLinkId'] = $paymentLinkId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentLinkResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/paymentlinks/{paymentLinkId}'), $this->getClientMetaInfo(), null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function cancelPaymentLinkById($paymentLinkId, CallContext $callContext = null)
    {
        $this->context['paymentLinkId'] = $paymentLinkId;
        $responseClassMap = new ResponseClassMap('');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/paymentlinks/{paymentLinkId}/cancel'), $this->getClientMetaInfo(), null, null, $callContext);
    }
}
