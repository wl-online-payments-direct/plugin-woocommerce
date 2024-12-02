<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Inpsyde\Logger\LoggerModule;
use Syde\Vendor\Inpsyde\Modularity\Module\Module;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentGatewayModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Checkout\CheckoutModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Config\ConfigModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Core\CoreModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Documentation\DocumentationModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Environment\EnvironmentModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPageModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Uri\UriModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Utils\UtilsModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting\VaultingModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WcSupport\WcSupportModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\WebhooksModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlineLogging\WorldlineLoggingModule;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WorldlinePaymentGatewayModule;
return static function (): iterable {
    return [new EnvironmentModule(), new CoreModule(), new PaymentGatewayModule(), new LoggerModule(), new WorldlineLoggingModule(), new UriModule(), new WcSupportModule(), new ConfigModule(), new WorldlinePaymentGatewayModule(), new CheckoutModule(), new ReturnPageModule(), new WebhooksModule(), new VaultingModule(), new UtilsModule(), new DocumentationModule()];
};
