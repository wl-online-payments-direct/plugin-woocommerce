<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\PaymentGateway;

interface PaymentRequestValidatorInterface
{
    /**
     * @param \WC_Order $order
     * @param PaymentGateway $gateway
     * @throws \RuntimeException
     */
    public function assertIsValid(\WC_Order $order, PaymentGateway $gateway) : void;
}
