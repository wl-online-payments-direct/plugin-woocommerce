<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Inpsyde\Transformer\ConfigurableTransformer;
use Syde\Vendor\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Dhii\Services\Factory;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\ThreeDSecure;
return new Factory(['config.authorization_mode', 'config.enforce_3dsv2', 'config.card_brands_grouped', 'vaulting.repository.wc.tokens'], static function (string $authorizationMode, bool $enforce3ds2, bool $cardBrandsGrouped, WcTokenRepository $wcTokenRepository): Transformer {
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
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use ($cardBrandsGrouped, $wcTokenRepository): HostedCheckoutSpecificInput {
        $specificInput = new HostedCheckoutSpecificInput();
        $specificInput->setReturnUrl($input->returnUrl());
        $cardSpecificInputForHostedCheckout = new CardPaymentMethodSpecificInputForHostedCheckout();
        $cardSpecificInputForHostedCheckout->setGroupCards($cardBrandsGrouped);
        $specificInput->setCardPaymentMethodSpecificInput($cardSpecificInputForHostedCheckout);
        $userId = \get_current_user_id();
        if ($userId > 0) {
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
    $transformer->addTransformer(static function (HostedCheckoutInput $input) use ($enforce3ds2, $authorizationMode): CardPaymentMethodSpecificInput {
        $threedSecure = new ThreeDSecure();
        $threedSecure->setSkipAuthentication(!$enforce3ds2);
        $cardSpecificInput = new CardPaymentMethodSpecificInput();
        $cardSpecificInput->setAuthorizationMode($authorizationMode);
        $token = $input->token();
        if (\is_string($token) && !empty($token)) {
            $cardSpecificInput->setToken($token);
            $cardSpecificInput->setUnscheduledCardOnFileRequestor('cardholderInitiated');
            $cardSpecificInput->setUnscheduledCardOnFileSequenceIndicator('subsequent');
            $threedSecure->setChallengeIndicator('no-challenge-requested');
        }
        $cardSpecificInput->setThreeDSecure($threedSecure);
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
