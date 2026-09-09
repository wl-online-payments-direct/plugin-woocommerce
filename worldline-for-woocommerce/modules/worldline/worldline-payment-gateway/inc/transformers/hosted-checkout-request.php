<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Dhii\Services\Factory;
use Worldline\Vendor\Worldline\Transformer\ConfigurableTransformer;
use Worldline\Vendor\Worldline\Transformer\Transformer;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\CardThreeDSecureFactory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\CarteBancaireThreeDSecureFactory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\GooglePayThreeDSecureFactory;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputBase;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\MobilePaymentMethodHostedCheckoutSpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\MobilePaymentProduct320SpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\PaymentProduct130SpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ThreeDSecure;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ThreeDSecureBase;
return new Factory(['config.authorization_mode', 'worldline_payment_gateway.3ds.card_3ds_factory', 'worldline_payment_gateway.3ds.carte_bancaire_3ds_factory', 'worldline_payment_gateway.3ds.google_pay_3ds_factory', 'config.card_brands_grouped', 'config.stored_card_buttons', 'vaulting.repository.wc.tokens.' . GatewayIds::HOSTED_CHECKOUT, 'config.hosted_checkout_page_template', 'config.show_payment_confirmation_page', 'config.session_timeout_minutes'], static function (string $authorizationMode, CardThreeDSecureFactory $cardThreedSecureFactory, CarteBancaireThreeDSecureFactory $carteBancaireThreedSecureFactory, GooglePayThreeDSecureFactory $gpayThreedSecureFactory, bool $cardBrandsGrouped, bool $showTokens, WcTokenRepository $wcTokenRepository, string $hostedCheckoutPageTemplate, bool $showPaymentConfirmationPage, int $sessionTimeoutMinutes) : Transformer {
    $transformer = new ConfigurableTransformer();
    $transformer->addTransformer(static function (HostedCheckoutInput $input, Transformer $transformer) : CreateHostedCheckoutRequest {
        $request = new CreateHostedCheckoutRequest();
        $request->setOrder($input->order());
        $request->setHostedCheckoutSpecificInput($transformer->create(HostedCheckoutSpecificInput::class, $input));
        $request->setCardPaymentMethodSpecificInput($transformer->create(CardPaymentMethodSpecificInputBase::class, $input));
        $request->setMobilePaymentMethodSpecificInput($transformer->create(MobilePaymentMethodHostedCheckoutSpecificInput::class, $input));
        $request->setRedirectPaymentMethodSpecificInput($transformer->create(RedirectPaymentMethodSpecificInput::class, $input));
        return $request;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($cardBrandsGrouped, $wcTokenRepository, $showTokens, $hostedCheckoutPageTemplate, $showPaymentConfirmationPage, $sessionTimeoutMinutes) : HostedCheckoutSpecificInput {
        $userId = $input->wcOrder()->get_user_id();
        $tokenizationAllowed = $showTokens && $userId > 0;
        $specificInput = new HostedCheckoutSpecificInput();
        $specificInput->setReturnUrl($input->returnUrl());
        $cardSpecificInputForHostedCheckout = new CardPaymentMethodSpecificInputForHostedCheckout();
        $cardSpecificInputForHostedCheckout->setGroupCards($cardBrandsGrouped);
        if (!$tokenizationAllowed) {
            $cardSpecificInputForHostedCheckout->setTokenizationMode('noTokenization');
        }
        $specificInput->setCardPaymentMethodSpecificInput($cardSpecificInputForHostedCheckout);
        $specificInput->setVariant($hostedCheckoutPageTemplate);
        $specificInput->setShowResultPage($showPaymentConfirmationPage);
        $specificInput->setSessionTimeout($sessionTimeoutMinutes);
        if ($tokenizationAllowed) {
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
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($cardThreedSecureFactory, $carteBancaireThreedSecureFactory, $authorizationMode) : CardPaymentMethodSpecificInputBase {
        $cardSpecificInput = new CardPaymentMethodSpecificInputBase();
        $cardSpecificInput->setAuthorizationMode($authorizationMode);
        $threeDSecureResult = $cardThreedSecureFactory->create($input->order()->getAmountOfMoney()->getAmount(), $input->order()->getAmountOfMoney()->getCurrencyCode());
        $threeDSecureBase = new ThreeDSecureBase();
        $threeDSecureBase->fromObject($threeDSecureResult->toObject());
        $cardSpecificInput->setThreeDSecure($threeDSecureBase);
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
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($cardThreedSecureFactory, $carteBancaireThreedSecureFactory, $authorizationMode) : CardPaymentMethodSpecificInput {
        $cardSpecificInput = new CardPaymentMethodSpecificInput();
        $cardSpecificInput->setAuthorizationMode($authorizationMode);
        $threeDSecureResult = $cardThreedSecureFactory->create($input->order()->getAmountOfMoney()->getAmount(), $input->order()->getAmountOfMoney()->getCurrencyCode());
        $threeDSecure = new ThreeDSecure();
        $threeDSecure->fromObject($threeDSecureResult->toObject());
        $cardSpecificInput->setThreeDSecure($threeDSecure);
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
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use($authorizationMode, $gpayThreedSecureFactory) : MobilePaymentMethodHostedCheckoutSpecificInput {
        $mobileSpecificInput = new MobilePaymentMethodHostedCheckoutSpecificInput();
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
