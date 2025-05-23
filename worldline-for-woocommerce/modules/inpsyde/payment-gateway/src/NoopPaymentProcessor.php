<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\PaymentGateway;

class NoopPaymentProcessor implements PaymentProcessorInterface
{
    public function processPayment(\WC_Order $order, PaymentGateway $gateway): array
    {
        return ['result' => 'success', 'redirect' => $gateway->get_return_url($order)];
    }
}
