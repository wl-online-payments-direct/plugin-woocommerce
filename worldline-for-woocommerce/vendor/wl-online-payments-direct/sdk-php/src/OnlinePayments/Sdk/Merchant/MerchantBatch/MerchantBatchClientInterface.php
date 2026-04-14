<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantBatch;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetBatchStatusResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SubmitBatchRequestBody;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SubmitBatchResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
/**
 * MerchantBatch client interface.
 */
interface MerchantBatchClientInterface
{
    /**
     * Resource /v2/{merchantId}/merchant-batches - Submit batch
     *
     * @param SubmitBatchRequestBody $body
     * @param CallContext|null $callContext
     * @return SubmitBatchResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function submitBatch(SubmitBatchRequestBody $body, ?CallContext $callContext = null) : SubmitBatchResponse;
    /**
     * Resource /v2/{merchantId}/merchant-batches/{merchantBatchReference}/process - Process batch transactions
     *
     * @param string $merchantBatchReference
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
    function processBatch(string $merchantBatchReference, ?CallContext $callContext = null) : void;
    /**
     * Resource /v2/{merchantId}/merchant-batches/{merchantBatchReference} - Get batch status
     *
     * @param string $merchantBatchReference
     * @param CallContext|null $callContext
     * @return GetBatchStatusResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getBatchStatus(string $merchantBatchReference, ?CallContext $callContext = null) : GetBatchStatusResponse;
}
