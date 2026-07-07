<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Worldline\Logger\LoggerModule;
use Worldline\Vendor\Worldline\Modularity\Module\Module;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\ApplePayGateway\ApplePayGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\BankTransferGateway\BankTransferGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\BlikGateway\BlikGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Checkout\CheckoutModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Config\ConfigModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Core\CoreModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\CVCOGateway\CVCOGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Documentation\DocumentationModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Environment\EnvironmentModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\GooglePayGateway\GooglePayGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\HostedTokenizationGateway\HostedTokenizationGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\IdealGateway\IdealGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\LinxoConnectGateway\LinxoConnectGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Orders\OrdersModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\PayPalGateway\PayPalGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\MealvouchersGateway\MealvouchersGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\PledgGateway\PledgGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\PostfinanceGateway\PostfinanceGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\KlarnaGateway\KlarnaGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\ProductType\ProductTypeModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Przelewy24Gateway\Przelewy24GatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage\ReturnPageModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\SepaDirectDebitGateway\SepaDirectDebitGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\TwintGateway\TwintGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Uninstall\UninstallModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Uri\UriModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Utils\UtilsModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\VaultingModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WcSupport\WcSupportModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\WebhooksModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlineLogging\WorldlineLoggingModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WorldlinePaymentGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\EpsGateway\EpsGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\IllicadoGateway\IllicadoGatewayModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WeroGateway\WeroGatewayModule;
return static function () : iterable {
    return [new EnvironmentModule(), new CoreModule(), new PaymentGatewayModule(), new LoggerModule(), new WorldlineLoggingModule(), new UriModule(), new WcSupportModule(), new ConfigModule(), new WorldlinePaymentGatewayModule(), new HostedTokenizationGatewayModule(), new GooglePayGatewayModule(), new ApplePayGatewayModule(), new BankTransferGatewayModule(), new IdealGatewayModule(), new EpsGatewayModule(), new PayPalGatewayModule(), new BlikGatewayModule(), new PledgGatewayModule(), new LinxoConnectGatewayModule(), new Przelewy24GatewayModule(), new PostfinanceGatewayModule(), new SepaDirectDebitGatewayModule(), new KlarnaGatewayModule(), new TwintGatewayModule(), new CheckoutModule(), new ReturnPageModule(), new WebhooksModule(), new VaultingModule(), new UtilsModule(), new DocumentationModule(), new UninstallModule(), new MealvouchersGatewayModule(), new ProductTypeModule(), new CVCOGatewayModule(), new IllicadoGatewayModule(), new WeroGatewayModule(), new OrdersModule()];
};
