<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting;

use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ExtendingModule;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PaymentOutput;
use Worldline\Vendor\OnlinePayments\Sdk\ReferenceException;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Throwable;
use WC_Order;
use WC_Payment_Gateway;
use WC_Payment_Token;
use WC_Payment_Token_CC;
use WC_Payment_Tokens;
class VaultingModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container) : bool
    {
        $this->addNewTokenHandler($container);
        $this->addStoredCardDeletionHandler($container);
        $this->mergeHostedCheckoutTokensIntoCardGateway($container);
        $this->filterStoredCardsOnCheckout($container);
        return \true;
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
    // phpcs:ignore Worldline.CodeQuality.FunctionLength.TooLong
    private function addNewTokenHandler(ContainerInterface $container) : void
    {
        \add_action('wlop.wc_order_status_updated', static function (array $args) use($container) : void {
            $wcOrder = $args['wcOrder'];
            \assert($wcOrder instanceof WC_Order);
            if (!\in_array($wcOrder->get_status(), [
                'on-hold',
                // authorized
                'processing',
                // captured
                'completed',
            ], \true) || \wc_string_to_bool((string) $wcOrder->get_meta(OrderMetaKeys::SAVED_TOKEN))) {
                return;
            }
            $userId = $wcOrder->get_user_id();
            // cannot save for guests
            if ($userId <= 0) {
                return;
            }
            $gatewayId = $wcOrder->get_payment_method();
            if (!\in_array($gatewayId, [GatewayIds::HOSTED_CHECKOUT, GatewayIds::HOSTED_TOKENIZATION], \true)) {
                return;
            }
            $paymentOutput = $args['paymentOutput'];
            \assert($paymentOutput instanceof PaymentOutput);
            $cardOutput = $paymentOutput->getCardPaymentMethodSpecificOutput();
            if (!$cardOutput) {
                return;
            }
            $card = $cardOutput->getCard();
            $token = $cardOutput->getToken();
            if (!$token || !$card) {
                return;
            }
            $apiClient = $container->get('worldline_payment_gateway.api.client');
            \assert($apiClient instanceof MerchantClientInterface);
            try {
                $tokenInfo = $apiClient->tokens()->getToken($token);
            } catch (ReferenceException $exception) {
                // seems to happen when HT token is not supposed to be saved
                return;
            } catch (Throwable $exception) {
                \do_action('wlop.card_token_get_info_error', ['token' => $token, 'userId' => $userId, 'exception' => $exception]);
                return;
            }
            if ($tokenInfo->getIsTemporary()) {
                return;
            }
            $wcTokenRepo = $container->get("vaulting.repository.wc.tokens.{$gatewayId}");
            \assert($wcTokenRepo instanceof WcTokenRepository);
            $wcTokenRepo->addCard($token, $userId, $card, $cardOutput->getPaymentProductId());
            $wcOrder->update_meta_data(OrderMetaKeys::SAVED_TOKEN, \wc_bool_to_string(\true));
        });
    }
    // phpcs:ignore Worldline.CodeQuality.NestingLevel.High
    private function addStoredCardDeletionHandler(ContainerInterface $container) : void
    {
        \add_action(
            'woocommerce_payment_token_deleted',
            /**
             * @psalm-suppress MissingClosureParamType
             */
            static function (string $tokenId, $token) use($container) : void {
                try {
                    if (!$token instanceof WC_Payment_Token_CC) {
                        return;
                    }
                    if ($token->get_gateway_id() !== GatewayIds::HOSTED_CHECKOUT && $token->get_gateway_id() !== GatewayIds::HOSTED_TOKENIZATION) {
                        return;
                    }
                    $apiClient = $container->get('worldline_payment_gateway.api.client');
                    \assert($apiClient instanceof MerchantClientInterface);
                    $apiClient->tokens()->deleteToken($token->get_token());
                    \do_action('wlop.card_token_deleted', ['last4' => $token->get_last4(), 'userId' => $token->get_user_id()]);
                } catch (Throwable $exception) {
                    \do_action('wlop.card_token_delete_error', ['token' => $token->get_token(), 'userId' => $token->get_user_id(), 'exception' => $exception]);
                }
            },
            10,
            2
        );
    }
    /**
     * Lists cards saved on Hosted Checkout under the card gateway as well.
     *
     * Tokens are partitioned by gateway id - WcTokenRepository::addCard() stamps the id the
     * card was saved through - so a card saved on the Worldline hosted page is invisible to the
     * card gateway. Shoppers have one wallet, not one per integration path, so both sets are
     * merged here.
     *
     * This one filter covers display and payment at once: WC_Payment_Gateway::get_tokens()
     * renders from it, and HostedTokenizationGatewayModule builds both the tokens it sends to
     * Worldline and the id-to-token map the iframe uses through the same repository call.
     */
    private function mergeHostedCheckoutTokensIntoCardGateway(ContainerInterface $container) : void
    {
        \add_filter(
            'woocommerce_get_customer_payment_tokens',
            /**
             * @param mixed $tokens
             * @param mixed $customerId
             * @param mixed $gatewayId
             *
             * @return mixed
             */
            function ($tokens, $customerId, $gatewayId) use($container) {
                if (!\is_array($tokens) || (string) $gatewayId !== GatewayIds::HOSTED_TOKENIZATION) {
                    return $tokens;
                }
                if (!$container->get('config.stored_card_buttons')) {
                    return $tokens;
                }
                return $this->withHostedCheckoutTokens($tokens, (int) $customerId);
            },
            10,
            3
        );
    }
    /**
     * @param array<int, WC_Payment_Token> $tokens
     *
     * @return array<int, WC_Payment_Token>
     */
    private function withHostedCheckoutTokens(array $tokens, int $customerId) : array
    {
        $hostedCheckoutTokens = WC_Payment_Tokens::get_customer_tokens($customerId, GatewayIds::HOSTED_CHECKOUT);
        foreach ($hostedCheckoutTokens as $token) {
            $tokens[$token->get_id()] = $token;
        }
        return $tokens;
    }
    /**
     * Hides the saved cards from the checkout when the card gateway is switched off.
     *
     * Every saved card is a card, and the card gateway is the only method that can charge one
     * from the shop checkout, so once it is disabled there is nothing left to offer the
     * shopper. WooCommerce drops the cards saved through the card gateway by itself - it only
     * lists tokens whose gateway is enabled - but a card saved on Hosted Checkout carries the
     * Hosted Checkout gateway id and would keep showing under that method, so both sets are
     * dropped here.
     *
     * Only the checkout is filtered; My account keeps listing the cards so they can still be
     * reviewed and deleted, and a card saved this way stays usable on the Worldline hosted
     * page, which receives the tokens over the API rather than through this list.
     */
    // phpcs:ignore Worldline.CodeQuality.NestingLevel.High
    private function filterStoredCardsOnCheckout(ContainerInterface $container) : void
    {
        \add_filter('woocommerce_saved_payment_methods_list', function (array $methods) use($container) : array {
            if (!\is_checkout() || empty($methods['cc'])) {
                return $methods;
            }
            if ($this->savedCardsOfferedOnCheckout($container)) {
                return $methods;
            }
            foreach ($methods['cc'] as $index => $method) {
                if (\in_array($method['method']['gateway'], [GatewayIds::HOSTED_CHECKOUT, GatewayIds::HOSTED_TOKENIZATION], \true)) {
                    unset($methods['cc'][$index]);
                }
            }
            return $methods;
        });
    }
    private function savedCardsOfferedOnCheckout(ContainerInterface $container) : bool
    {
        return (bool) $container->get('config.stored_card_buttons') && $this->cardGatewayEnabled();
    }
    /**
     * Whether the card gateway is switched on in the plugin configuration. Availability is not
     * consulted on purpose: WooCommerce keeps a saved card on the block checkout for as long as
     * its gateway is enabled, so reading the very same flag makes both sets of cards appear and
     * disappear together.
     */
    private function cardGatewayEnabled() : bool
    {
        if (!\function_exists('WC') || !\WC()->payment_gateways()) {
            return \false;
        }
        $gateways = \WC()->payment_gateways()->payment_gateways();
        $cardGateway = $gateways[GatewayIds::HOSTED_TOKENIZATION] ?? null;
        return $cardGateway instanceof WC_Payment_Gateway && $cardGateway->enabled === 'yes';
    }
}
