<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\CofSeries;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\AuthorizationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ImportCofSeriesRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ImportCofSeriesResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\IdempotenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\PlatformException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
/**
 * CofSeries client interface.
 */
interface CofSeriesClientInterface
{
    /**
     * Resource /v2/{merchantId}/tokens/importCofSeries - Imports the COF Series token.
     *
     * @param ImportCofSeriesRequest $body
     * @param CallContext|null $callContext
     * @return ImportCofSeriesResponse
     *
     * @throws IdempotenceException
     * @throws ValidationException
     * @throws AuthorizationException
     * @throws ReferenceException
     * @throws PlatformException
     * @throws ApiException
     * @throws InvalidResponseException
     */
    function importCofSeries(ImportCofSeriesRequest $body, ?CallContext $callContext = null) : ImportCofSeriesResponse;
}
