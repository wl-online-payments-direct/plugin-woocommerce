<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\PledgGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Customer;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\OrderReferences;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectionData;
class PledgRequestModifier extends AbstractHostedPaymentRequestModifier
{
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $redirectPaymentMethodSpecificInput = $hostedCheckoutRequest->getRedirectPaymentMethodSpecificInput();
        $cardPaymentMethodSpecificInput = $hostedCheckoutRequest->getCardPaymentMethodSpecificInput();
        $mobilePaymentMethodSpecificInput = $hostedCheckoutRequest->getMobilePaymentMethodSpecificInput();
        $mobilePaymentMethodSpecificInput->setAuthorizationMode('SALE');
        $cardPaymentMethodSpecificInput->setAuthorizationMode('SALE');
        $hostedCheckoutRequest->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);
        $hostedCheckoutRequest->setMobilePaymentMethodSpecificInput($mobilePaymentMethodSpecificInput);
        $redirectionData = new RedirectionData();
        $redirectionData->setReturnUrl($hostedCheckoutInput->returnUrl());
        $redirectPaymentMethodSpecificInput->setPaymentProductId(5300);
        $redirectPaymentMethodSpecificInput->setRequiresApproval(\false);
        $redirectPaymentMethodSpecificInput->setRedirectionData($redirectionData);
        $hostedCheckoutRequest->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);
        $order = $hostedCheckoutRequest->getOrder();
        $wcOrder = $hostedCheckoutInput->wcOrder();
        $customer = $order->getCustomer() ?: new Customer();
        if ($customer->getMerchantCustomerId() === null) {
            $customer->setMerchantCustomerId((string) $wcOrder->get_customer_id());
        }
        if ($customer->getLocale() === null) {
            $customer->setLocale($hostedCheckoutRequest->getHostedCheckoutSpecificInput()->getLocale());
        }
        $order->setCustomer($customer);
        $references = $order->getReferences() ?: new OrderReferences();
        $settings = \get_option('woocommerce_worldline-for-woocommerce_settings', []);
        $descriptorSetting = $settings['fixed_soft_descriptor'] ?? '';
        if (!empty($descriptorSetting)) {
            $references->setDescriptor(\substr($descriptorSetting, 0, 15));
        } else {
            $merchantName = \substr(\get_bloginfo('name'), 0, 15);
            $references->setDescriptor($merchantName);
        }
        $references->setMerchantReference((string) $wcOrder->get_id());
        $order->setReferences($references);
        $hostedCheckoutRequest->setOrder($order);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
}
