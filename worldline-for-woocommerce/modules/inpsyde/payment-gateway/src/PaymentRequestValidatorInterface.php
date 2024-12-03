<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\PaymentGateway;

interface PaymentRequestValidatorInterface
{
    /**
     * @param \WC_Order $order
     * @param PaymentGateway $param
     * @throws \RuntimeException
     */
    public function assertIsValid(\WC_Order $order, PaymentGateway $param): void;
}
