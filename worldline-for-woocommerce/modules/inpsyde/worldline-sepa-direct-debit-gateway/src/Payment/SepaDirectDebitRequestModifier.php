<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\SepaDirectDebitGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateMandateRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentProduct771SpecificInput;
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
