<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\ProductGroups;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\GetPaymentProductGroupsResponse;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentProductGroup;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
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
