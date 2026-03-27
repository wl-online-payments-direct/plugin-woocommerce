<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Przelewy24Gateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\OrderReferences;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectionData;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
class Przelewy24RequestModifier extends AbstractHostedPaymentRequestModifier
{
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectionData = new RedirectionData();
        $redirectionData->setReturnUrl($hostedCheckoutInput->returnUrl());
        $redirectPaymentMethodSpecificInput->setPaymentProductId(3124);
        $redirectPaymentMethodSpecificInput->setRedirectionData($redirectionData);
        $hostedCheckoutRequest->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);
        $order = $hostedCheckoutRequest->getOrder();
        $references = $order->getReferences() ?: new OrderReferences();
        $settings = \get_option('woocommerce_worldline-for-woocommerce_settings', []);
        $descriptorSetting = $settings['fixed_soft_descriptor'] ?? '';
        if (!empty($descriptorSetting)) {
            $references->setDescriptor(\substr($descriptorSetting, 0, 15));
        } else {
            $merchantName = \substr(\get_bloginfo('name'), 0, 15);
            $references->setDescriptor($merchantName);
        }
        $hostedCheckoutRequest->setOrder($order);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
}
