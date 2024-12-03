<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Tokens;

use Syde\Vendor\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateTokenRequest;
use Syde\Vendor\OnlinePayments\Sdk\ResponseClassMap;
class TokensClient extends ApiResource implements TokensClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function createToken(CreateTokenRequest $body, CallContext $callContext = null)
    {
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\CreatedTokenResponse');
        return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/tokens'), $this->getClientMetaInfo(), $body, null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function getToken($tokenId, CallContext $callContext = null)
    {
        $this->context['tokenId'] = $tokenId;
        $responseClassMap = new ResponseClassMap('Syde\Vendor\OnlinePayments\Sdk\Domain\TokenResponse');
        return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/tokens/{tokenId}'), $this->getClientMetaInfo(), null, $callContext);
    }
    /**
     * {@inheritDoc}
     */
    public function deleteToken($tokenId, CallContext $callContext = null)
    {
        $this->context['tokenId'] = $tokenId;
        $responseClassMap = new ResponseClassMap('');
        return $this->getCommunicator()->delete($responseClassMap, $this->instantiateUri('/v2/{merchantId}/tokens/{tokenId}'), $this->getClientMetaInfo(), null, $callContext);
    }
}
