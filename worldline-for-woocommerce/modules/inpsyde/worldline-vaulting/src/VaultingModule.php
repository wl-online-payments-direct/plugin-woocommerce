<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting;

use Syde\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentOutput;
use Syde\Vendor\Psr\Container\ContainerInterface;
use Syde\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Throwable;
use WC_Cart;
use WC_Order;
use WC_Payment_Token_CC;
class VaultingModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container): bool
    {
        $this->addNewTokenHandler($container);
        $this->addStoredCardDeletionHandler($container);
        $this->addStoredCardButtons($container);
        $this->filterStoredCardsOnBlockCheckout($container);
        return \true;
    }
    public function services(): array
    {
        static $services;
        if ($services === null) {
            $services = require_once dirname(__DIR__) . '/inc/services.php';
        }
        return $services();
    }
    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = require_once dirname(__DIR__) . '/inc/extensions.php';
        }
        return $extensions();
    }
    private function addNewTokenHandler(ContainerInterface $container): void
    {
        add_action('wlop.wc_order_status_updated', static function (array $args) use ($container): void {
            $wcOrder = $args['wcOrder'];
            assert($wcOrder instanceof WC_Order);
            if (!in_array($wcOrder->get_status(), [
                'on-hold',
                // authorized
                'processing',
            ], \true) || wc_string_to_bool((string) $wcOrder->get_meta(OrderMetaKeys::SAVED_TOKEN))) {
                return;
            }
            $userId = $wcOrder->get_user_id();
            // cannot save for guests
            if ($userId <= 0) {
                return;
            }
            $paymentOutput = $args['paymentOutput'];
            assert($paymentOutput instanceof PaymentOutput);
            $cardOutput = $paymentOutput->getCardPaymentMethodSpecificOutput();
            if (!$cardOutput) {
                return;
            }
            $card = $cardOutput->getCard();
            $token = $cardOutput->getToken();
            if (!$token || !$card) {
                return;
            }
            $wcTokenRepo = $container->get('vaulting.repository.wc.tokens');
            assert($wcTokenRepo instanceof WcTokenRepository);
            $wcTokenRepo->addCard($token, $userId, $card, $cardOutput->getPaymentProductId());
            $wcOrder->update_meta_data(OrderMetaKeys::SAVED_TOKEN, wc_bool_to_string(\true));
        });
    }
    // phpcs:ignore Inpsyde.CodeQuality.NestingLevel.High
    private function addStoredCardDeletionHandler(ContainerInterface $container): void
    {
        add_action(
            'woocommerce_payment_token_deleted',
            /**
             * @psalm-suppress MissingClosureParamType
             */
            static function (string $tokenId, $token) use ($container): void {
                try {
                    if (!$token instanceof WC_Payment_Token_CC) {
                        return;
                    }
                    $gatewayId = $container->get('worldline_payment_gateway.id');
                    if ($token->get_gateway_id() !== $gatewayId) {
                        return;
                    }
                    $apiClient = $container->get('worldline_payment_gateway.api.client');
                    assert($apiClient instanceof MerchantClientInterface);
                    $apiClient->tokens()->deleteToken($token->get_token());
                } catch (Throwable $exception) {
                    do_action('wlop.card_token_delete_error', ['token' => $token->get_token(), 'userId' => $token->get_user_id(), 'exception' => $exception]);
                }
            },
            10,
            2
        );
    }
    private function addStoredCardButtons(ContainerInterface $container): void
    {
        $tokenButtonsHook = (string) apply_filters('wlop_checkout_saved_cards_renderer_hook', 'woocommerce_review_order_before_payment');
        add_action($tokenButtonsHook, static function () use ($container): void {
            if (!$container->get('config.stored_card_buttons')) {
                return;
            }
            $gateway = $container->get('worldline_payment_gateway.gateway');
            assert($gateway instanceof PaymentGateway);
            if (!$gateway->is_available()) {
                return;
            }
            $cart = WC()->cart;
            if (!$cart instanceof WC_Cart) {
                return;
            }
            $total = (float) $cart->get_total('numeric');
            if ($total <= 0) {
                return;
            }
            $wcTokenRepo = $container->get('vaulting.repository.wc.tokens');
            assert($wcTokenRepo instanceof WcTokenRepository);
            $renderer = $container->get('vaulting.card_button_renderer');
            assert($renderer instanceof CardButtonRenderer);
            $tokens = $wcTokenRepo->sortedCustomerTokens(get_current_user_id());
            $tokens = array_slice($tokens, 0, 3);
            if (empty($tokens)) {
                return;
            }
            echo '<div class="wlop-saved-card-buttons-wrapper">';
            foreach ($tokens as $token) {
                // phpcs:ignore WordPress.Security.EscapeOutput
                echo $renderer->render($token);
            }
            echo '</div>';
        });
    }
    // phpcs:ignore Inpsyde.CodeQuality.NestingLevel.High
    private function filterStoredCardsOnBlockCheckout(ContainerInterface $container): void
    {
        add_filter('woocommerce_saved_payment_methods_list', static function (array $methods) use ($container) {
            if (!is_checkout() || empty($methods['cc'])) {
                return $methods;
            }
            if ($container->get('config.stored_card_buttons')) {
                return $methods;
            }
            $wlopPaymentGatewayId = $container->get('worldline_payment_gateway.id');
            foreach ($methods['cc'] as $index => $method) {
                if ($method['method']['gateway'] === $wlopPaymentGatewayId) {
                    unset($methods['cc'][$index]);
                }
            }
            return $methods;
        });
    }
}
