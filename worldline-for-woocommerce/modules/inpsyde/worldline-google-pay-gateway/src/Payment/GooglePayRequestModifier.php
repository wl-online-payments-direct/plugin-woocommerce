<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\GooglePayGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\ThreeDSecureFactory;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectionData;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ThreeDSecure;
class GooglePayRequestModifier extends AbstractHostedPaymentRequestModifier
{
    private ThreeDSecureFactory $threedSecureFactory;
    private string $authorizationMode;
    public function __construct(string $authorizationMode, ThreeDSecureFactory $threedSecureFactory)
    {
        $this->threedSecureFactory = $threedSecureFactory;
        $this->authorizationMode = $authorizationMode;
    }
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput): CreateHostedCheckoutRequest
    {
        $mobilePaymentSpecificInput = new MobilePaymentMethodSpecificInput();
        $mobilePaymentSpecificInput->setAuthorizationMode($this->authorizationMode);
        $mobilePaymentSpecificInput->setPaymentProductId(320);
        $mobilePaymentSpecificInput->setPaymentProduct320SpecificInput($this->threeDSecure($hostedCheckoutInput));
        $hostedCheckoutRequest->setMobilePaymentMethodSpecificInput($mobilePaymentSpecificInput);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
    protected function threeDSecure(HostedCheckoutInput $hostedCheckoutInput): ThreeDSecure
    {
        $threedSecure = $this->threedSecureFactory->create($hostedCheckoutInput->order()->getAmountOfMoney()->getAmount(), $hostedCheckoutInput->order()->getAmountOfMoney()->getCurrencyCode(), $hostedCheckoutInput->wcOrder()->get_checkout_order_received_url());
        return $threedSecure;
    }
}
