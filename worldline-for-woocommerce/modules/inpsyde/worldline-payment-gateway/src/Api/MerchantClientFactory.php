<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\Client;
use Syde\Vendor\OnlinePayments\Sdk\Communicator;
use Syde\Vendor\OnlinePayments\Sdk\CommunicatorConfiguration;
use Syde\Vendor\OnlinePayments\Sdk\CommunicatorLogger;
use Syde\Vendor\OnlinePayments\Sdk\DefaultConnection;
use Syde\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
class MerchantClientFactory
{
    protected string $integrator;
    private ?CommunicatorLogger $sdkLogger;
    public function __construct(string $integrator, ?CommunicatorLogger $sdkLogger = null)
    {
        $this->integrator = $integrator;
        $this->sdkLogger = $sdkLogger;
    }
    /**
     * @throws Exception
     */
    public function create(string $pspid, string $apiKey, string $apiSecret, string $apiEndpoint): MerchantClientInterface
    {
        $connection = new DefaultConnection();
        $communicatorConfiguration = new CommunicatorConfiguration($apiKey, $apiSecret, $apiEndpoint, $this->integrator);
        $communicator = new Communicator($connection, $communicatorConfiguration);
        $client = new Client($communicator);
        if ($this->sdkLogger) {
            $client->enableLogging($this->sdkLogger);
        }
        return $client->merchant($pspid);
    }
}
