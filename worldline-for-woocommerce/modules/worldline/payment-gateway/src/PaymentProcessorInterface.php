<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\PaymentGateway;

interface PaymentProcessorInterface
{
    public function processPayment(\WC_Order $order, PaymentGateway $gateway) : array;
}
