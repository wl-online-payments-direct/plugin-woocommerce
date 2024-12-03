<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\OnlinePayments\Sdk\Merchant\Mandates;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateMandateRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateMandateResponse;
use Syde\Vendor\OnlinePayments\Sdk\Domain\GetMandateResponse;
use Syde\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\OnlinePayments\Sdk\InvalidResponseException;
use Syde\Vendor\OnlinePayments\Sdk\PaymentPlatformException;
use Syde\Vendor\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
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
