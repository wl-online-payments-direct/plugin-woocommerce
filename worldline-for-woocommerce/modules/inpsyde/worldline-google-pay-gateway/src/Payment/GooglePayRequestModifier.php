<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\GooglePayGateway\Payment;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\ThreeDSecureFactory;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\MobilePaymentMethodSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\RedirectionData;
use Syde\Vendor\OnlinePayments\Sdk\Domain\ThreeDSecure;
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
