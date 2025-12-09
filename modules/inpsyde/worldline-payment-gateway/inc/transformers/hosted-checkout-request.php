<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Inpsyde\Transformer\ConfigurableTransformer;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\CardThreeDSecureFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\CarteBancaireThreeDSecureFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\GooglePayThreeDSecureFactory;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProduct130SpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
return new Factory(['config.authorization_mode', 'worldline_payment_gateway.3ds.card_3ds_factory', 'worldline_payment_gateway.3ds.carte_bancaire_3ds_factory', 'worldline_payment_gateway.3ds.google_pay_3ds_factory', 'config.card_brands_grouped', 'config.stored_card_buttons', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'config.hosted_checkout_page_template'], static function (string $authorizationMode, CardThreeDSecureFactory $cardThreedSecureFactory, CarteBancaireThreeDSecureFactory $carteBancaireThreedSecureFactory, GooglePayThreeDSecureFactory $gpayThreedSecureFactory, bool $cardBrandsGrouped, bool $showTokens, WcTokenRepository $wcTokenRepository, string $hostedCheckoutPageTemplate) : Transformer {
    $transformer = new ConfigurableTransformer();
    $transformer->addTransformer(static function (HostedCheckoutInput $input, Transformer $transformer) : CreateHostedCheckoutRequest {
        $request = new CreateHostedCheckoutRequest();
        $request->setOrder($input->order());
        $request->setHostedCheckoutSpecificInput($transformer->create(HostedCheckoutSpecificInput::class, $input));
        $request->setCardPaymentMethodSpecificInput($transformer->create(CardPaymentMethodSpecificInput::class, $input));
        $request->setMobilePaymentMethodSpecificInput($transformer->create(MobilePaymentMethodSpecificInput::class, $input));
        $request->setRedirectPaymentMethodSpecificInput($transformer->create(RedirectPaymentMethodSpecificInput::class, $input));
        return $request;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($cardBrandsGrouped, $wcTokenRepository, $showTokens, $hostedCheckoutPageTemplate) : HostedCheckoutSpecificInput {
        $specificInput = new HostedCheckoutSpecificInput();
        $specificInput->setReturnUrl($input->returnUrl());
        $cardSpecificInputForHostedCheckout = new CardPaymentMethodSpecificInputForHostedCheckout();
        $cardSpecificInputForHostedCheckout->setGroupCards($cardBrandsGrouped);
        $specificInput->setCardPaymentMethodSpecificInput($cardSpecificInputForHostedCheckout);
        $specificInput->setVariant($hostedCheckoutPageTemplate);
        $userId = \get_current_user_id();
        if ($showTokens && $userId > 0) {
            $tokens = $wcTokenRepository->customerTokens($userId);
            if (!empty($tokens)) {
                $tokensStr = \implode(',', \array_map(static function (\WC_Payment_Token $token) : string {
                    return $token->get_token();
                }, $tokens));
                $specificInput->setTokens($tokensStr);
            }
        }
        $language = $input->language();
        if (!\is_null($language)) {
            $specificInput->setLocale($language);
        }
        return $specificInput;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($cardThreedSecureFactory, $carteBancaireThreedSecureFactory, $authorizationMode) : CardPaymentMethodSpecificInput {
        $cardSpecificInput = new CardPaymentMethodSpecificInput();
        $cardSpecificInput->setAuthorizationMode($authorizationMode);
        $cardSpecificInput->setThreeDSecure($cardThreedSecureFactory->create($input->order()->getAmountOfMoney()->getAmount(), $input->order()->getAmountOfMoney()->getCurrencyCode()));
        $token = $input->token();
        if (\is_string($token) && !empty($token)) {
            $cardSpecificInput->setToken($token);
            $cardSpecificInput->setUnscheduledCardOnFileRequestor('cardholderInitiated');
            $cardSpecificInput->setUnscheduledCardOnFileSequenceIndicator('subsequent');
        }
        $carteBancaireSpecificInput = new PaymentProduct130SpecificInput();
        $carteBancaireSpecificInput->setThreeDSecure($carteBancaireThreedSecureFactory->create($input->order()->getAmountOfMoney()->getAmount(), $input->order()->getAmountOfMoney()->getCurrencyCode(), (int) $input->wcOrder()->get_item_count()));
        if ($carteBancaireSpecificInput->getThreeDSecure() !== null) {
            $cardSpecificInput->setPaymentProduct130SpecificInput($carteBancaireSpecificInput);
        }
        return $cardSpecificInput;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($authorizationMode, $gpayThreedSecureFactory) : MobilePaymentMethodSpecificInput {
        $mobileSpecificInput = new MobilePaymentMethodSpecificInput();
        $mobileSpecificInput->setAuthorizationMode($authorizationMode);
        $gpaySpecificInput = new MobilePaymentProduct320SpecificInput();
        $gpaySpecificInput->setThreeDSecure($gpayThreedSecureFactory->create($input->order()->getAmountOfMoney()->getAmount(), $input->order()->getAmountOfMoney()->getCurrencyCode()));
        $mobileSpecificInput->setPaymentProduct320SpecificInput($gpaySpecificInput);
        return $mobileSpecificInput;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($authorizationMode) : RedirectPaymentMethodSpecificInput {
        $redirectSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectSpecificInput->setRequiresApproval($authorizationMode !== AuthorizationMode::SALE);
        return $redirectSpecificInput;
    });
    return $transformer;
});
