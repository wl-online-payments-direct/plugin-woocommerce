<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\GooglePayGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\GooglePayThreeDSecureFactory;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\GPayThreeDSecure;
class GooglePayRequestModifier extends AbstractHostedPaymentRequestModifier
{
    private GooglePayThreeDSecureFactory $threedSecureFactory;
    public function __construct(GooglePayThreeDSecureFactory $threedSecureFactory)
    {
        $this->threedSecureFactory = $threedSecureFactory;
    }
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $mobilePaymentSpecificInput = $hostedCheckoutRequest->getMobilePaymentMethodSpecificInput();
        $mobilePaymentSpecificInput->setPaymentProductId(320);
        $gpaySpecificInput = $mobilePaymentSpecificInput->getPaymentProduct320SpecificInput();
        $gpaySpecificInput->setThreeDSecure($this->threeDSecure($hostedCheckoutInput));
        $mobilePaymentSpecificInput->setPaymentProduct320SpecificInput($gpaySpecificInput);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        $hostedCheckoutRequest->setCardPaymentMethodSpecificInput(null);
        return $hostedCheckoutRequest;
    }
    protected function threeDSecure(HostedCheckoutInput $hostedCheckoutInput) : GPayThreeDSecure
    {
        $threedSecure = $this->threedSecureFactory->create($hostedCheckoutInput->order()->getAmountOfMoney()->getAmount(), $hostedCheckoutInput->order()->getAmountOfMoney()->getCurrencyCode(), $hostedCheckoutInput->wcOrder()->get_checkout_order_received_url());
        return $threedSecure;
    }
}
