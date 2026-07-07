<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\ApplePayGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\MobilePaymentMethodHostedCheckoutSpecificInput;
class ApplePayRequestModifier extends AbstractHostedPaymentRequestModifier
{
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $mobilePaymentMethodSpecificInput = new MobilePaymentMethodHostedCheckoutSpecificInput();
        $authorizationMode = $hostedCheckoutRequest->getMobilePaymentMethodSpecificInput()->getAuthorizationMode();
        $mobilePaymentMethodSpecificInput->setAuthorizationMode($authorizationMode);
        $mobilePaymentMethodSpecificInput->setPaymentProductId(302);
        $hostedCheckoutRequest->setMobilePaymentMethodSpecificInput($mobilePaymentMethodSpecificInput);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
}
