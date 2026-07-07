<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk;

use Worldline\Vendor\OnlinePayments\Sdk\Logging\CommunicatorLogger;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
/**
 * Payment platform client interface.
 */
interface ClientInterface
{
    /**
     * @param CommunicatorLogger $communicatorLogger
     */
    function enableLogging(CommunicatorLogger $communicatorLogger) : void;
    /**
     * @return void
     */
    function disableLogging() : void;
    /**
     * @param string $clientMetaInfo
     * @return $this
     */
    function setClientMetaInfo(string $clientMetaInfo) : ClientInterface;
    /**
     * Resource /v2/{merchantId}
     *
     * @param string $merchantId
     * @return MerchantClientInterface
     */
    function merchant(string $merchantId) : MerchantClientInterface;
}
