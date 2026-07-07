<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\BankTransferGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RedirectionData;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
class BankTransferRequestModifier extends AbstractHostedPaymentRequestModifier
{
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectionData = new RedirectionData();
        $redirectionData->setReturnUrl($hostedCheckoutInput->returnUrl());
        $redirectPaymentMethodSpecificInput->setPaymentProductId(5408);
        $redirectPaymentMethodSpecificInput->setRedirectionData($redirectionData);
        $redirectPaymentMethodSpecificInput->setPaymentProduct5408SpecificInput($hostedCheckoutRequest->getRedirectPaymentMethodSpecificInput()->getPaymentProduct5408SpecificInput());
        $settings = \get_option('woocommerce_worldline-bank-transfer_settings', []);
        $redirectPaymentMethodSpecificInput->getPaymentProduct5408SpecificInput()->setInstantPaymentOnly(($settings['instant_payment'] ?? 'yes') === 'yes');
        $hostedCheckoutRequest->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
}
