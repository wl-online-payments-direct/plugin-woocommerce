<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factory;
use Syde\Vendor\Inpsyde\Transformer\ConfigurableTransformer;
use Syde\Vendor\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\ThreeDSecureFactory;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentProduct130SpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PaymentProduct130SpecificThreeDSecure;
use Syde\Vendor\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
return new Factory(['config.authorization_mode', 'worldline_payment_gateway.three_d_secure_factory', 'config.card_brands_grouped', 'config.stored_card_buttons', 'vaulting.repository.wc.tokens', 'config.hosted_checkout_page_template'], static function (string $authorizationMode, ThreeDSecureFactory $threedSecureFactory, bool $cardBrandsGrouped, bool $showTokens, WcTokenRepository $wcTokenRepository, string $hostedCheckoutPageTemplate): Transformer {
    $transformer = new ConfigurableTransformer();
    $transformer->addTransformer(static function (HostedCheckoutInput $input, Transformer $transformer): CreateHostedCheckoutRequest {
        $request = new CreateHostedCheckoutRequest();
        $request->setOrder($input->order());
        $request->setHostedCheckoutSpecificInput($transformer->create(HostedCheckoutSpecificInput::class, $input));
        $request->setCardPaymentMethodSpecificInput($transformer->create(CardPaymentMethodSpecificInput::class, $input));
        $request->setMobilePaymentMethodSpecificInput($transformer->create(MobilePaymentMethodSpecificInput::class, $input));
        $request->setRedirectPaymentMethodSpecificInput($transformer->create(RedirectPaymentMethodSpecificInput::class, $input));
        return $request;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use ($cardBrandsGrouped, $wcTokenRepository, $showTokens, $hostedCheckoutPageTemplate): HostedCheckoutSpecificInput {
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
                $tokensStr = \implode(',', \array_map(static function (\WC_Payment_Token $token): string {
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
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use ($threedSecureFactory, $authorizationMode): CardPaymentMethodSpecificInput {
        $cardSpecificInput = new CardPaymentMethodSpecificInput();
        $cardSpecificInput->setAuthorizationMode($authorizationMode);
        $threedSecure = $threedSecureFactory->create($input->order()->getAmountOfMoney()->getAmount(), $input->order()->getAmountOfMoney()->getCurrencyCode());
        $cardSpecificInput->setThreeDSecure($threedSecure);
        $token = $input->token();
        if (\is_string($token) && !empty($token)) {
            $cardSpecificInput->setToken($token);
            $cardSpecificInput->setUnscheduledCardOnFileRequestor('cardholderInitiated');
            $cardSpecificInput->setUnscheduledCardOnFileSequenceIndicator('subsequent');
        }
        $carteBancaireSpecificInput = new PaymentProduct130SpecificInput();
        $carteBancaire3ds = new PaymentProduct130SpecificThreeDSecure();
        $carteBancaire3ds->setUsecase($authorizationMode === AuthorizationMode::SALE ? 'single-amount' : 'payment-upon-shipment');
        $carteBancaire3ds->setNumberOfItems((int) $input->wcOrder()->get_item_count());
        $carteBancaireSpecificInput->setThreeDSecure($carteBancaire3ds);
        $cardSpecificInput->setPaymentProduct130SpecificInput($carteBancaireSpecificInput);
        return $cardSpecificInput;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use ($authorizationMode): MobilePaymentMethodSpecificInput {
        $mobileSpecificInput = new MobilePaymentMethodSpecificInput();
        $mobileSpecificInput->setAuthorizationMode($authorizationMode);
        return $mobileSpecificInput;
    });
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use ($authorizationMode): RedirectPaymentMethodSpecificInput {
        $redirectSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectSpecificInput->setRequiresApproval($authorizationMode !== AuthorizationMode::SALE);
        return $redirectSpecificInput;
    });
    return $transformer;
});
