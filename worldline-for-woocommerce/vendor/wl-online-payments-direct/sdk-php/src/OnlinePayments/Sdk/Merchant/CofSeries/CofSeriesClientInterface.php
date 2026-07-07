<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant\CofSeries;

use Worldline\Vendor\OnlinePayments\Sdk\ApiException;
use Worldline\Vendor\OnlinePayments\Sdk\AuthorizationException;
use Worldline\Vendor\OnlinePayments\Sdk\CallContext;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ImportCofSeriesRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ImportCofSeriesResponse;
use Worldline\Vendor\OnlinePayments\Sdk\IdempotenceException;
use Worldline\Vendor\OnlinePayments\Sdk\PlatformException;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\OnlinePayments\Sdk\ValidationException;
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
