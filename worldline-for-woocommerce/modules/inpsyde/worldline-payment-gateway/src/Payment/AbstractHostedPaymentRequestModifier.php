<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
abstract class AbstractHostedPaymentRequestModifier
{
    abstract public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput): CreateHostedCheckoutRequest;
    protected function removeTokensFromRequest(CreateHostedCheckoutRequest $hostedCheckoutRequest): void
    {
        $hostedCheckoutSpecificInput = $hostedCheckoutRequest->getHostedCheckoutSpecificInput();
        $hostedCheckoutSpecificInput->setTokens('');
        $hostedCheckoutRequest->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);
    }
}
