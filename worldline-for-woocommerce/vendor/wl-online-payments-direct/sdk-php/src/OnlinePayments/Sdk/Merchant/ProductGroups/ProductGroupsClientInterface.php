<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\ProductGroups;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetPaymentProductGroupsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProductGroup;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface ProductGroupsClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/productgroups - Get product groups
     *
     * @param GetProductGroupsParams $query
     * @param CallContext $callContext
     * @return GetPaymentProductGroupsResponse
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     */
    public function getProductGroups(GetProductGroupsParams $query, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/productgroups/{paymentProductGroupId} - Get product group
     *
     * @param string $paymentProductGroupId
     * @param GetProductGroupParams $query
     * @param CallContext $callContext
     * @return PaymentProductGroup
     *
     * @throws ApiException
     * @throws AuthorizationException
     * @throws Exception
     * @throws PaymentPlatformException
     * @throws IdempotenceException
     * @throws InvalidResponseException
     * @throws ReferenceException
     * @throws ValidationException
     */
    public function getProductGroup($paymentProductGroupId, GetProductGroupParams $query, CallContext $callContext = null);
}
