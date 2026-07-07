<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\Webhooks;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\SendTestRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ValidateCredentialsRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ValidateCredentialsResponse;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
/**
 * Webhooks client interface.
 */
interface WebhooksClientInterface
{
    /**
     * Resource /v2/{merchantId}/webhooks/validateCredentials - Validate credentials
     *
     * @param ValidateCredentialsRequest $body
     * @param CallContext|null $callContext
     * @return ValidateCredentialsResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function validateWebhookCredentials(ValidateCredentialsRequest $body, ?CallContext $callContext = null) : ValidateCredentialsResponse;
    /**
     * Resource /v2/{merchantId}/webhooks/sendtest - Send test
     *
     * @param SendTestRequest $body
     * @param CallContext|null $callContext
     * @return null
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function sendTestWebhook(SendTestRequest $body, ?CallContext $callContext = null) : void;
}
