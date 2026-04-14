<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokens;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ErrorResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateTokenRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatedTokenResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\TokenResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ExceptionFactory;
/**
 * Tokens client.
 */
class TokensClient extends ApiResource implements TokensClientInterface
{
    /** @var ExceptionFactory|null */
    private ?ExceptionFactory $responseExceptionFactory = null;
    /**
     * @inheritdoc
     */
    public function createToken(CreateTokenRequest $body, ?CallContext $callContext = null) : CreatedTokenResponse
    {
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\CreatedTokenResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->post($responseClassMap, $this->instantiateUri('/v2/{merchantId}/tokens'), $this->getClientMetaInfo(), $body, null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function getToken(string $tokenId, ?CallContext $callContext = null) : TokenResponse
    {
        $this->context['tokenId'] = $tokenId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultSuccessResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\TokenResponse';
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            return $this->getCommunicator()->get($responseClassMap, $this->instantiateUri('/v2/{merchantId}/tokens/{tokenId}'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /**
     * @inheritdoc
     */
    public function deleteToken(string $tokenId, ?CallContext $callContext = null) : void
    {
        $this->context['tokenId'] = $tokenId;
        $responseClassMap = new ResponseClassMap();
        $responseClassMap->defaultErrorResponseClassName = 'Syde\\Vendor\\Worldline\\OnlinePayments\\Sdk\\Domain\\ErrorResponse';
        try {
            $this->getCommunicator()->delete($responseClassMap, $this->instantiateUri('/v2/{merchantId}/tokens/{tokenId}'), $this->getClientMetaInfo(), null, $callContext);
        } catch (ErrorResponseException $e) {
            throw $this->getResponseExceptionFactory()->createException($e->getHttpStatusCode(), $e->getErrorResponse(), $callContext);
        }
    }
    /** @return ExceptionFactory */
    private function getResponseExceptionFactory() : ExceptionFactory
    {
        if (\is_null($this->responseExceptionFactory)) {
            $this->responseExceptionFactory = new ExceptionFactory();
        }
        return $this->responseExceptionFactory;
    }
}
