<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AuthorizationMode;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
class WeroRequestModifier extends AbstractHostedPaymentRequestModifier
{
    private string $authorizationMode;
    public function __construct(string $authorizationMode)
    {
        $this->authorizationMode = $authorizationMode;
    }
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $hostedCheckoutRequest->setCardPaymentMethodSpecificInput(null);
        $hostedCheckoutRequest->setMobilePaymentMethodSpecificInput(null);
        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectPaymentMethodSpecificInput->setPaymentProductId(900);
        $order = $hostedCheckoutRequest->getOrder();
        if ($order !== null) {
            $references = $order->getReferences();
            if ($references !== null) {
                $descriptor = \get_bloginfo('name');
                if (!empty($descriptor)) {
                    $references->setDescriptor($descriptor);
                }
            }
        }
        $redirectPaymentMethodSpecificInput->setRequiresApproval($this->authorizationMode !== AuthorizationMode::SALE);
        $hostedCheckoutRequest->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
}
