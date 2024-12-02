<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Products;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentProduct;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentProductNetworksResponse;
use Syde\Vendor\OnlinePayments\Sdk\Domain\ProductDirectory;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
interface ProductsClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/products - Get payment products
     *
     * @param GetPaymentProductsParams $query
     * @param CallContext $callContext
     * @return GetPaymentProductsResponse
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
    public function getPaymentProducts(GetPaymentProductsParams $query, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/products/{paymentProductId} - Get payment product
     *
     * @param int $paymentProductId
     * @param GetPaymentProductParams $query
     * @param CallContext $callContext
     * @return PaymentProduct
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
    public function getPaymentProduct($paymentProductId, GetPaymentProductParams $query, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/products/{paymentProductId}/directory - Get payment product directory
     *
     * @param int $paymentProductId
     * @param GetProductDirectoryParams $query
     * @param CallContext $callContext
     * @return ProductDirectory
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
    public function getProductDirectory($paymentProductId, GetProductDirectoryParams $query, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/products/{paymentProductId}/networks - Get payment product networks
     *
     * @param int $paymentProductId
     * @param GetPaymentProductNetworksParams $query
     * @param CallContext $callContext
     * @return PaymentProductNetworksResponse
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
    public function getPaymentProductNetworks($paymentProductId, GetPaymentProductNetworksParams $query, CallContext $callContext = null);
}
