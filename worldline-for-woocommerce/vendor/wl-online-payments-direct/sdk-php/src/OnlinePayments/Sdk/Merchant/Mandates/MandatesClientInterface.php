<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Mandates;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateMandateRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateMandateResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\GetMandateResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
interface MandatesClientInterface
{
    /**
     * ApiResource /v2/{merchantId}/mandates - Create mandate
     *
     * @param CreateMandateRequest $body
     * @param CallContext $callContext
     * @return CreateMandateResponse
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
    public function createMandate(CreateMandateRequest $body, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/mandates/{uniqueMandateReference} - Get mandate
     *
     * @param string $uniqueMandateReference
     * @param CallContext $callContext
     * @return GetMandateResponse
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
    public function getMandate($uniqueMandateReference, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/mandates/{uniqueMandateReference}/block - Block mandate
     *
     * @param string $uniqueMandateReference
     * @param CallContext $callContext
     * @return GetMandateResponse
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
    public function blockMandate($uniqueMandateReference, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/mandates/{uniqueMandateReference}/unblock - Unblock mandate
     *
     * @param string $uniqueMandateReference
     * @param CallContext $callContext
     * @return GetMandateResponse
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
    public function unblockMandate($uniqueMandateReference, CallContext $callContext = null);
    /**
     * ApiResource /v2/{merchantId}/mandates/{uniqueMandateReference}/revoke - Revoke mandate
     *
     * @param string $uniqueMandateReference
     * @param CallContext $callContext
     * @return GetMandateResponse
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
    public function revokeMandate($uniqueMandateReference, CallContext $callContext = null);
}
