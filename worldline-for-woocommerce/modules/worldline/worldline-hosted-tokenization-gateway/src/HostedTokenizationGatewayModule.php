<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\HostedTokenizationGateway;

use Worldline\Assets\AssetManager;
use Worldline\Assets\Script;
use Worldline\Assets\Style;
use Worldline\Assets\Asset;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\Modularity\Module\ExtendingModule;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentGateway;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Worldline\Vendor\Psr\Container\ContainerExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Container\NotFoundExceptionInterface;
use WC_Cart;
use WC_Order;
use WC_Payment_Token;
// phpcs:disable Worldline.CodeQuality.NestingLevel.High
// phpcs:disable Worldline.CodeQuality.FunctionLength.TooLong
class HostedTokenizationGatewayModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public const PACKAGE_NAME = 'worldline-hosted-tokenization-gateway';
    /**
     * Request-scoped cache for makeFrontendConfig().
     *
     * The config is requested once per registered script, and building it with
     * a URL issues a createHostedTokenization call. Both the classic and the
     * blocks script share the same builder, so a single checkout render used to
     * create several hosted tokenization sessions and discard all but one.
     *
     * @var array<string, array<string, mixed>>
     */
    private array $frontendConfigCache = [];
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action(AssetManager::ACTION_SETUP, function (AssetManager $assetManager) use($container) : void {
            if (!$this->isGatewayEnabled($container) || !$this->isCheckoutContext()) {
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
    /**
     * The card field, the external tokenizer script and the Worldline API call
     * in makeFrontendConfig() are only needed where checkout is rendered.
     * Registering them on every front-end page caused needless external
     * requests (and a site-wide fatal when the API was unavailable), so the
     * whole asset registration is restricted to the checkout/order-pay pages.
     */
    private function isCheckoutContext() : bool
    {
        return \is_checkout() || \is_checkout_pay_page();
    }
    private function handleConfigAjax(ContainerInterface $container) : void
    {
        $nonce = isset($_POST['nonce']) ? \sanitize_text_field(\wp_unslash($_POST['nonce'])) : '';
        if (!\wp_verify_nonce($nonce, 'wlop_hosted_tokenization_config')) {
            \wp_send_json_error(['message' => \__('Invalid security token.', 'worldline-for-woocommerce')], 403);
            return;
        }
        $withoutUrl = isset($_POST['withoutUrl']) && $_POST['withoutUrl'] === 'true';
        if (!$withoutUrl && $this->isPaidCallThrottled($container)) {
            \wp_send_json_error(['message' => \__('Too many requests.', 'worldline-for-woocommerce')], 429);
            return;
        }
        \wp_send_json_success($this->makeFrontendConfig($container, $withoutUrl));
    }
    /**
     * Per-client token bucket guarding the paid Worldline API call. A generous
     * burst is allowed so legitimate checkout retries (e.g. the guest
     * checkout_error iframe reset) are never blocked.
     */
    private function isPaidCallThrottled(ContainerInterface $container) : bool
    {
        $transientKey = 'wlop_ht_throttle_' . \md5($this->throttleClientKey($container));
        $count = (int) \get_transient($transientKey);
        if ($count >= 30) {
            return \true;
        }
        \set_transient($transientKey, $count + 1, \MINUTE_IN_SECONDS);
        return \false;
    }
    /**
     * Prefer the WooCommerce session customer id (survives CDN/proxy better
     * than a raw IP); fall back to the REMOTE_ADDR-based client IP service.
     */
    private function throttleClientKey(ContainerInterface $container) : string
    {
        $session = \WC()->session;
        if ($session !== null) {
            $customerId = (string) $session->get_customer_id();
            if ($customerId !== '') {
                return $customerId;
            }
        }
        $clientIp = $container->get('utils.client_ip_address');
        return \is_string($clientIp) && $clientIp !== '' ? $clientIp : 'unknown';
    }
    public function makeFrontendConfig(ContainerInterface $container, bool $withoutUrl = \false) : array
    {
        $cacheKey = $withoutUrl ? 'without_url' : 'with_url';
        if (isset($this->frontendConfigCache[$cacheKey])) {
            return $this->frontendConfigCache[$cacheKey];
        }
        $currencyCode = \get_woocommerce_currency();
        $gatewayId = GatewayIds::HOSTED_TOKENIZATION;
        $config = ['ajax' => \admin_url('admin-ajax.php'), 'nonce' => \wp_create_nonce('wlop_hosted_tokenization_config'), 'gateway' => ['id' => $gatewayId], 'wrapper' => ['id' => 'wlop_ht'], 'currency' => [
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
            try {
                $response = $client->hostedTokenization()->createHostedTokenization($request);
                $config['url'] = $response->getHostedTokenizationUrl();
            } catch (\Throwable $exception) {
                \do_action('wlop.hosted_tokenization_config_error', ['exception' => $exception]);
            }
        }
        $total = $this->determineTotal($container);
        if (!\is_null($total)) {
            $config['total'] = $total;
        }
        $this->frontendConfigCache[$cacheKey] = $config;
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
            /*
             * Wait for a concurrent writer rather than skipping. The payment is
             * created server-side a few hundred milliseconds before this page
             * loads, so Worldline's webhook and this request race for the order
             * lock almost every time - and dropping this write leaves the page
             * rendering a status that is already out of date.
             */
            $orderUpdater->update($wlopWcOrder, OrderUpdater::INTERACTIVE_LOCK_WAIT_SECONDS);
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
