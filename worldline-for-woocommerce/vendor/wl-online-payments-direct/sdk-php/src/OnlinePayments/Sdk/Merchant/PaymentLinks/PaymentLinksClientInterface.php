<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\PaymentLinks;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentLinkRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PaymentLinkResponse;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
/**
 * PaymentLinks client interface.
 */
interface PaymentLinksClientInterface
{
    /**
     * Resource /v2/{merchantId}/paymentlinks - Create payment link
     *
     * @param CreatePaymentLinkRequest $body
     * @param CallContext|null $callContext
     * @return PaymentLinkResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function createPaymentLink(CreatePaymentLinkRequest $body, ?CallContext $callContext = null) : PaymentLinkResponse;
    /**
     * Resource /v2/{merchantId}/paymentlinks/{paymentLinkId} - Get payment link by ID
     *
     * @param string $paymentLinkId
     * @param CallContext|null $callContext
     * @return PaymentLinkResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function getPaymentLinkById(string $paymentLinkId, ?CallContext $callContext = null) : PaymentLinkResponse;
    /**
     * Resource /v2/{merchantId}/paymentlinks/{paymentLinkId}/cancel - Cancel PaymentLink by ID
     *
     * @param string $paymentLinkId
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
    function cancelPaymentLinkById(string $paymentLinkId, ?CallContext $callContext = null) : void;
}
