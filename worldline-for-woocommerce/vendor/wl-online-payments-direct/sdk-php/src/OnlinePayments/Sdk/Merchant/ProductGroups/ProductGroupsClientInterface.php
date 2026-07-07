<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\ProductGroups;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\GetPaymentProductGroupsResponse;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PaymentProductGroup;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
/**
 * ProductGroups client interface.
 */
interface ProductGroupsClientInterface
{
    /**
     * Resource /v2/{merchantId}/productgroups - Get product groups
     *
     * @param GetProductGroupsParams $query
     * @param CallContext|null $callContext
     * @return GetPaymentProductGroupsResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getProductGroups(GetProductGroupsParams $query, ?CallContext $callContext = null) : GetPaymentProductGroupsResponse;
    /**
     * Resource /v2/{merchantId}/productgroups/{paymentProductGroupId} - Get product group
     *
     * @param string $paymentProductGroupId
     * @param GetProductGroupParams $query
     * @param CallContext|null $callContext
     * @return PaymentProductGroup
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getProductGroup(string $paymentProductGroupId, GetProductGroupParams $query, ?CallContext $callContext = null) : PaymentProductGroup;
}
