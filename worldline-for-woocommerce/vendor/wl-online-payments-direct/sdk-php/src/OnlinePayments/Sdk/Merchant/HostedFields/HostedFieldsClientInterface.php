<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedFields;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedFieldsSessionRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedFieldsSessionResponse;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
/**
 * HostedFields client interface.
 */
interface HostedFieldsClientInterface
{
    /**
     * Resource /v2/{merchantId}/hostedfields/sessions - Create hosted fields session
     *
     * @param CreateHostedFieldsSessionRequest $body
     * @param CallContext|null $callContext
     * @return CreateHostedFieldsSessionResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function createHostedFieldsSession(CreateHostedFieldsSessionRequest $body, ?CallContext $callContext = null) : CreateHostedFieldsSessionResponse;
}
