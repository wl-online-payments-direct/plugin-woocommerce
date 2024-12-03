<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\PaymentGateway;

class NoopPaymentRequestValidator implements PaymentRequestValidatorInterface
{
    public function assertIsValid(\WC_Order $order, PaymentGateway $param): void
    {
        // TODO: Implement assertIsValid() method.
    }
}
