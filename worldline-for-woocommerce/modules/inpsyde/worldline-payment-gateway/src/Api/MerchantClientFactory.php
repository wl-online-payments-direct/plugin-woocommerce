<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Properties\PluginProperties;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment\WpEnvironment;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment\WpEnvironmentInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Authentication\V1HmacAuthenticator;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Client;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communicator;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CommunicatorConfiguration;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Logging\CommunicatorLogger;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\DefaultConnection;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ShoppingCartExtension;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
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
