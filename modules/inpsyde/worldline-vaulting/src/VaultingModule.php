<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentOutput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ReferenceException;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Throwable;
use WC_Cart;
use WC_Order;
use WC_Payment_Token_CC;
class VaultingModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container) : bool
    {
        $this->addNewTokenHandler($container);
        $this->addStoredCardDeletionHandler($container);
        $this->addCheckoutStoredCardButtons($container);
        $this->addPayOrderStoredCardButtons($container);
        $this->filterStoredCardsOnBlockCheckout($container);
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
    // phpcs:ignore Inpsyde.CodeQuality.FunctionLength.TooLong
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
    // phpcs:ignore Inpsyde.CodeQuality.NestingLevel.High
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
                    if ($token->get_gateway_id() !== GatewayIds::HOSTED_CHECKOUT) {
                        return;
                    }
                    $apiClient = $container->get('worldline_payment_gateway.api.client');
                    \assert($apiClient instanceof MerchantClientInterface);
                    $apiClient->tokens()->deleteToken($token->get_token());
                } catch (Throwable $exception) {
                    \do_action('wlop.card_token_delete_error', ['token' => $token->get_token(), 'userId' => $token->get_user_id(), 'exception' => $exception]);
                }
            },
            10,
            2
        );
    }
    private function renderStoredCardButtons(ContainerInterface $container) : string
    {
        $gatewayId = GatewayIds::HOSTED_CHECKOUT;
        $wcTokenRepo = $container->get("vaulting.repository.wc.tokens.{$gatewayId}");
        \assert($wcTokenRepo instanceof WcTokenRepository);
        $renderer = $container->get('vaulting.card_button_renderer');
        \assert($renderer instanceof CardButtonRenderer);
        $tokens = $wcTokenRepo->sortedCustomerTokens(\get_current_user_id());
        $tokens = \array_slice($tokens, 0, 3);
        if (empty($tokens)) {
            return '';
        }
        $html = '<div class="wlop-saved-card-buttons-wrapper">';
        foreach ($tokens as $token) {
            $html .= $renderer->render($token);
        }
        $html .= '</div>';
        return $html;
    }
    private function addCheckoutStoredCardButtons(ContainerInterface $container) : void
    {
        $tokenButtonsHook = (string) \apply_filters('wlop_checkout_saved_cards_renderer_hook', 'woocommerce_review_order_before_payment');
        \add_action($tokenButtonsHook, function () use($container) : void {
            if (!$container->get('config.stored_card_buttons')) {
                return;
            }
            $gateway = $container->get('worldline_payment_gateway.gateway');
            \assert($gateway instanceof PaymentGateway);
            if (!$gateway->is_available()) {
                return;
            }
            $cart = \WC()->cart;
            if (!$cart instanceof WC_Cart) {
                return;
            }
            $total = (float) $cart->get_total('numeric');
            if ($total <= 0) {
                return;
            }
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $this->renderStoredCardButtons($container);
        });
    }
    private function addPayOrderStoredCardButtons(ContainerInterface $container) : void
    {
        $tokenButtonsHook = (string) \apply_filters('wlop_pay_order_saved_cards_renderer_hook', 'woocommerce_pay_order_before_payment');
        \add_action($tokenButtonsHook, function () use($container) : void {
            if (!$container->get('config.stored_card_buttons')) {
                return;
            }
            global $wp;
            if (!isset($wp->query_vars['order-pay'])) {
                return;
            }
            $orderId = \absint($wp->query_vars['order-pay']);
            $order = \wc_get_order($orderId);
            if (!$order instanceof WC_Order) {
                return;
            }
            $total = (float) $order->get_total();
            if ($total <= 0) {
                return;
            }
            $gateway = $container->get('worldline_payment_gateway.gateway');
            \assert($gateway instanceof PaymentGateway);
            if (!$gateway->is_available()) {
                return;
            }
            // phpcs:ignore WordPress.Security.EscapeOutput
            echo $this->renderStoredCardButtons($container);
        });
    }
    // phpcs:ignore Inpsyde.CodeQuality.NestingLevel.High
    private function filterStoredCardsOnBlockCheckout(ContainerInterface $container) : void
    {
        \add_filter('woocommerce_saved_payment_methods_list', static function (array $methods) use($container) {
            if (!\is_checkout() || empty($methods['cc'])) {
                return $methods;
            }
            if ($container->get('config.stored_card_buttons')) {
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
}
