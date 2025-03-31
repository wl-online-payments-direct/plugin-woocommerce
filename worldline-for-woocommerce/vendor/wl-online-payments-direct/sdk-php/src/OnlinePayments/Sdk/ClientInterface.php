<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
/**
 * API specifications
 */
interface ClientInterface
{
    /**
     * @param CommunicatorLogger $communicatorLogger
     */
    function enableLogging(CommunicatorLogger $communicatorLogger);
    function disableLogging();
    /**
     * ApiResource /v2/{merchantId}
     *
     * @param string $merchantId
     * @return MerchantClientInterface
     */
    public function merchant($merchantId);
}
