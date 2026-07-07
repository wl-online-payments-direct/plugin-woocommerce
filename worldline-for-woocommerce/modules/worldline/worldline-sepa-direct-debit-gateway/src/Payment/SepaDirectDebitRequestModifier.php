<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\SepaDirectDebitGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateMandateRequest;
final class SepaDirectDebitRequestModifier extends AbstractHostedPaymentRequestModifier
{
    private const PRODUCT_ID = 771;
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $sepaMethodInput = $hostedCheckoutRequest->getSepaDirectDebitPaymentMethodSpecificInput();
        if ($sepaMethodInput) {
            $sepaMethodInput->setPaymentProductId(self::PRODUCT_ID);
            $hostedCheckoutRequest->setSepaDirectDebitPaymentMethodSpecificInput($sepaMethodInput);
        }
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
}
