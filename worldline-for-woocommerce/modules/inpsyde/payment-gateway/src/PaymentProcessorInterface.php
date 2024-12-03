<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\PaymentGateway;

interface PaymentProcessorInterface
{
    public function processPayment(\WC_Order $order, PaymentGateway $gateway): array;
}
