<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Exception;
use Worldline\Vendor\Worldline\Modularity\Properties\PluginProperties;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Environment\WpEnvironment;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Environment\WpEnvironmentInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Authentication\V1HmacAuthenticator;
use Worldline\Vendor\OnlinePayments\Sdk\Client;
use Worldline\Vendor\OnlinePayments\Sdk\Communicator;
use Worldline\Vendor\OnlinePayments\Sdk\CommunicatorConfiguration;
use Worldline\Vendor\OnlinePayments\Sdk\Logging\CommunicatorLogger;
use Worldline\Vendor\OnlinePayments\Sdk\Communication\DefaultConnection;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ShoppingCartExtension;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
class MerchantClientFactory
{
    protected string $integrator;
    protected string $pluginVersion;
    protected WpEnvironmentInterface $environment;
    private ?CommunicatorLogger $sdkLogger;
    public function __construct(PluginProperties $properties, WpEnvironmentInterface $environment, string $integrator, ?CommunicatorLogger $sdkLogger = null)
    {
        $this->pluginVersion = $properties->version();
        $this->environment = $environment;
        $this->integrator = $integrator;
        $this->sdkLogger = $sdkLogger;
    }
    /**
     * @throws Exception
     */
    public function create(string $pspid, string $apiKey, string $apiSecret, string $apiEndpoint) : MerchantClientInterface
    {
        $connection = new DefaultConnection();
        $communicatorConfiguration = new CommunicatorConfiguration($apiKey, $apiSecret, $apiEndpoint, $this->integrator);
        $communicatorConfiguration->setShoppingCartExtension(new ShoppingCartExtension('Worldline-GlobalOnlinePayments', 'WordPress', $this->getPlatformVersion(), $this->pluginVersion));
        $communicator = new Communicator($communicatorConfiguration, new V1HmacAuthenticator($communicatorConfiguration), $connection, null);
        $client = new Client($communicator);
        if ($this->sdkLogger) {
            $client->enableLogging($this->sdkLogger);
        }
        return $client->merchant($pspid);
    }
    private function getPlatformVersion() : string
    {
        return \sprintf("WordPress %s | WooCommerce %s", $this->environment->wpVersion(), $this->environment->wcVersion());
    }
}
