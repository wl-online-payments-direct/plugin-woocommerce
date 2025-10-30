<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
abstract class AbstractHostedPaymentRequestModifier
{
    public abstract function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest;
    protected function removeTokensFromRequest(CreateHostedCheckoutRequest $hostedCheckoutRequest) : void
    {
        $hostedCheckoutSpecificInput = $hostedCheckoutRequest->getHostedCheckoutSpecificInput();
        $hostedCheckoutSpecificInput->setTokens('');
        $hostedCheckoutRequest->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);
    }
}
