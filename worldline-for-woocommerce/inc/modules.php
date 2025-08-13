<?php

declare(strict_types=1);

use Inpsyde\Logger\LoggerModule;
use Inpsyde\Modularity\Module\Module;
use Inpsyde\PaymentGateway\PaymentGatewayModule;
use Inpsyde\WorldlineForWoocommerce\ApplePayGateway\ApplePayGatewayModule;
use Inpsyde\WorldlineForWoocommerce\BankTransferGateway\BankTransferGatewayModule;
use Inpsyde\WorldlineForWoocommerce\Checkout\CheckoutModule;
use Inpsyde\WorldlineForWoocommerce\Config\ConfigModule;
use Inpsyde\WorldlineForWoocommerce\Core\CoreModule;
use Inpsyde\WorldlineForWoocommerce\CVCOGateway\CVCOGatewayModule;
use Inpsyde\WorldlineForWoocommerce\Documentation\DocumentationModule;
use Inpsyde\WorldlineForWoocommerce\Environment\EnvironmentModule;
use Inpsyde\WorldlineForWoocommerce\GooglePayGateway\GooglePayGatewayModule;
use Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway\HostedTokenizationGatewayModule;
use Inpsyde\WorldlineForWoocommerce\IdealGateway\IdealGatewayModule;
use Inpsyde\WorldlineForWoocommerce\MealvouchersGateway\MealvouchersGatewayModule;
use Inpsyde\WorldlineForWoocommerce\PostfinanceGateway\PostfinanceGatewayModule;
use Inpsyde\WorldlineForWoocommerce\KlarnaGateway\KlarnaGatewayModule;
use Inpsyde\WorldlineForWoocommerce\ProductType\ProductTypeModule;
use Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPageModule;
use Inpsyde\WorldlineForWoocommerce\TwintGateway\TwintGatewayModule;
use Inpsyde\WorldlineForWoocommerce\Uninstall\UninstallModule;
use Inpsyde\WorldlineForWoocommerce\Uri\UriModule;
use Inpsyde\WorldlineForWoocommerce\Utils\UtilsModule;
use Inpsyde\WorldlineForWoocommerce\Vaulting\VaultingModule;
use Inpsyde\WorldlineForWoocommerce\WcSupport\WcSupportModule;
use Inpsyde\WorldlineForWoocommerce\Webhooks\WebhooksModule;
use Inpsyde\WorldlineForWoocommerce\WorldlineLogging\WorldlineLoggingModule;
use Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WorldlinePaymentGatewayModule;
use Inpsyde\WorldlineForWoocommerce\EpsGateway\EpsGatewayModule;

return /**
 * @return iterable<Module>
 */
    static function (): iterable {
        return [
            new EnvironmentModule(),
            new CoreModule(),
            new PaymentGatewayModule(),
            new LoggerModule(),
            new WorldlineLoggingModule(),
            new UriModule(),
            new WcSupportModule(),
            new ConfigModule(),
            new WorldlinePaymentGatewayModule(),
            new HostedTokenizationGatewayModule(),
            new GooglePayGatewayModule(),
            new ApplePayGatewayModule(),
            new BankTransferGatewayModule(),
            new IdealGatewayModule(),
            new EpsGatewayModule(),
            new PostfinanceGatewayModule(),
            new KlarnaGatewayModule(),
            new TwintGatewayModule(),
            new CheckoutModule(),
            new ReturnPageModule(),
            new WebhooksModule(),
            new VaultingModule(),
            new UtilsModule(),
            new DocumentationModule(),
            new UninstallModule(),
            new MealvouchersGatewayModule(),
            new ProductTypeModule(),
            new CVCOGatewayModule(),
        ];
    };
