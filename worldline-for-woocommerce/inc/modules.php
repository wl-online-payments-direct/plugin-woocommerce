<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\Logger\LoggerModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\Module;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ApplePayGateway\ApplePayGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\BankTransferGateway\BankTransferGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Checkout\CheckoutModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\ConfigModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core\CoreModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\CVCOGateway\CVCOGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Documentation\DocumentationModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment\EnvironmentModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\GooglePayGateway\GooglePayGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway\HostedTokenizationGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\IdealGateway\IdealGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PayPalGateway\PayPalGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\MealvouchersGateway\MealvouchersGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PostfinanceGateway\PostfinanceGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\KlarnaGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ProductType\ProductTypeModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPageModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\TwintGateway\TwintGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uninstall\UninstallModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uri\UriModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Utils\UtilsModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\VaultingModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WcSupport\WcSupportModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\WebhooksModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlineLogging\WorldlineLoggingModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WorldlinePaymentGatewayModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\EpsGateway\EpsGatewayModule;
return static function () : iterable {
    return [new EnvironmentModule(), new CoreModule(), new PaymentGatewayModule(), new LoggerModule(), new WorldlineLoggingModule(), new UriModule(), new WcSupportModule(), new ConfigModule(), new WorldlinePaymentGatewayModule(), new HostedTokenizationGatewayModule(), new GooglePayGatewayModule(), new ApplePayGatewayModule(), new BankTransferGatewayModule(), new IdealGatewayModule(), new EpsGatewayModule(), new PayPalGatewayModule(), new PostfinanceGatewayModule(), new KlarnaGatewayModule(), new TwintGatewayModule(), new CheckoutModule(), new ReturnPageModule(), new WebhooksModule(), new VaultingModule(), new UtilsModule(), new DocumentationModule(), new UninstallModule(), new MealvouchersGatewayModule(), new ProductTypeModule(), new CVCOGatewayModule()];
};
