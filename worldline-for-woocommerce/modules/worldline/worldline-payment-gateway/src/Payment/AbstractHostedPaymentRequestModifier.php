<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
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
