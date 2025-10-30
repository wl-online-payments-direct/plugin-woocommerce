<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway;

use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Inpsyde\Assets\Asset;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Container\NotFoundExceptionInterface;
use WC_Cart;
use WC_Order;
use WC_Payment_Token;
// phpcs:disable Inpsyde.CodeQuality.NestingLevel.High
// phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
class HostedTokenizationGatewayModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public const PACKAGE_NAME = 'worldline-hosted-tokenization-gateway';
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action(AssetManager::ACTION_SETUP, function (AssetManager $assetManager) use($container) : void {
            if (!$this->isGatewayEnabled($container)) {
                return;
            }
            $moduleName = 'hosted-tokenization-gateway';
            /** @var callable(string,string):string $getModuleAssetUrl */
            $getModuleAssetUrl = $container->get('assets.get_module_asset_url');
            $assetManager->register((new Script("{$moduleName}", $getModuleAssetUrl($moduleName, 'frontend-main.js'), Asset::FRONTEND))->withLocalize('WlopHtConfig', function () use($container) : array {
                return $this->makeFrontendConfig($container);
            })->withTranslation('worldline-for-woocommerce', \WP_PLUGIN_DIR . '/worldline-for-woocommerce/languages/'), (new Script("{$moduleName}-blocks", $getModuleAssetUrl($moduleName, 'frontend-blocks.js'), Asset::FRONTEND))->withLocalize('WlopHtConfig', function () use($container) : array {
                return $this->makeFrontendConfig($container);
            })->withTranslation('worldline-for-woocommerce', \WP_PLUGIN_DIR . '/worldline-for-woocommerce/languages/'), new Script('wlop-tokenizer', $container->get('config.api_endpoint') . '/hostedtokenization/js/client/tokenizer.min.js', Asset::FRONTEND));
        });
        \add_action('wp_ajax_wlop_hosted_tokenization_config', function () use($container) {
            $this->handleConfigAjax($container);
        });
        \add_action('wp_ajax_nopriv_wlop_hosted_tokenization_config', function () use($container) {
            $this->handleConfigAjax($container);
        });
        $this->registerCheckoutCompletionHandler($container);
        return \true;
    }
    private function isGatewayEnabled(ContainerInterface $container) : bool
    {
        $gateway = $container->get('hosted_tokenization_gateway.gateway');
        \assert($gateway instanceof PaymentGateway);
        return $gateway->is_available();
    }
    private function handleConfigAjax(ContainerInterface $container) : void
    {
        // phpcs:ignore WordPress.Security.NonceVerification
        $withoutUrl = isset($_POST['withoutUrl']) && $_POST['withoutUrl'] === 'true';
        \wp_send_json_success($this->makeFrontendConfig($container, $withoutUrl));
    }
    public function makeFrontendConfig(ContainerInterface $container, bool $withoutUrl = \false) : array
    {
        $currencyCode = \get_woocommerce_currency();
        $gatewayId = GatewayIds::HOSTED_TOKENIZATION;
        $config = ['ajax' => \admin_url('admin-ajax.php'), 'gateway' => ['id' => $gatewayId], 'wrapper' => ['id' => 'wlop_ht'], 'currency' => [
            'centFactor' => 100,
            // for future currencies support
            // from https://github.com/woocommerce/woocommerce/blob/89068601d334953e2904ecf56f528fc271c7b9ec/plugins/woocommerce/src/Internal/Admin/Settings.php#L97-L103
            'code' => $currencyCode,
            'precision' => \wc_get_price_decimals(),
            'symbol' => \html_entity_decode(\get_woocommerce_currency_symbol($currencyCode)),
            'symbolPosition' => \get_option('woocommerce_currency_pos'),
            'decimalSeparator' => \wc_get_price_decimal_separator(),
            'thousandSeparator' => \wc_get_price_thousand_separator(),
            'priceFormat' => \html_entity_decode(\get_woocommerce_price_format()),
        ], 'locale' => $container->get('worldline_payment_gateway.locale')];
        if ($container->get('config.surcharge_enabled')) {
            $config['surcharge'] = ['wrapper' => ['id' => 'wlop_ht_surcharge_note']];
        }
        if (!$withoutUrl) {
            $wcTokenRepo = $container->get("vaulting.repository.wc.tokens.{$gatewayId}");
            \assert($wcTokenRepo instanceof WcTokenRepository);
            $client = $container->get('worldline_payment_gateway.api.client');
            \assert($client instanceof MerchantClientInterface);
            $template = $container->get('config.hosted_tokenization_page_template');
            $request = new CreateHostedTokenizationRequest();
            if (!\is_null($template)) {
                $request->setVariant($template);
            }
            $request->setLocale((string) $container->get('worldline_payment_gateway.locale'));
            $request->setAskConsumerConsent(\true);
            $userId = \get_current_user_id();
            if ($userId > 0) {
                $tokens = $wcTokenRepo->customerTokens($userId);
                if (!empty($tokens)) {
                    $tokensStr = \implode(',', \array_map(static function (WC_Payment_Token $token) : string {
                        return $token->get_token();
                    }, $tokens));
                    $request->setTokens($tokensStr);
                    $tokensMap = [];
                    foreach ($tokens as $token) {
                        $tokensMap[$token->get_id()] = $token->get_token();
                    }
                    $config['tokens'] = $tokensMap;
                }
            }
            $response = $client->hostedTokenization()->createHostedTokenization($request);
            $config['url'] = $response->getHostedTokenizationUrl();
        }
        $total = $this->determineTotal($container);
        if (!\is_null($total)) {
            $config['total'] = $total;
        }
        return $config;
    }
    private function determineTotal(ContainerInterface $container) : ?int
    {
        global $wp;
        $moneyConverter = $container->get('worldline_payment_gateway.money_amount_converter');
        \assert($moneyConverter instanceof MoneyAmountConverter);
        if (\is_checkout_pay_page()) {
            $wcOrderId = \absint($wp->query_vars['order-pay']);
            if ($wcOrderId <= 0) {
                return 0;
            }
            $wcOrder = \wc_get_order($wcOrderId);
            if (!$wcOrder instanceof WC_Order) {
                return 0;
            }
            return $moneyConverter->decimalValueToCentValue((float) $wcOrder->get_total(), $wcOrder->get_currency());
        }
        if (isset(\WC()->cart) && \WC()->cart instanceof WC_Cart) {
            return $moneyConverter->decimalValueToCentValue((float) \WC()->cart->get_total('numeric'), \get_woocommerce_currency());
        }
        return null;
    }
    public function registerCheckoutCompletionHandler(ContainerInterface $container) : void
    {
        \add_action('wlop_order_received_page', static function (WC_Order $wcOrder) use($container) : void {
            if ($wcOrder->get_payment_method() !== GatewayIds::HOSTED_TOKENIZATION) {
                return;
            }
            $wlopWcOrder = new WlopWcOrder($wcOrder);
            $orderUpdater = $container->get('worldline_payment_gateway.order_updater');
            \assert($orderUpdater instanceof OrderUpdater);
            $orderUpdater->update($wlopWcOrder);
        });
    }
    public function services() : array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
        }
        return $services();
    }
    /**
     * @inheritDoc
     */
    public function extensions() : array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = (require_once \dirname(__DIR__) . '/inc/extensions.php');
        }
        return $extensions();
    }
}
