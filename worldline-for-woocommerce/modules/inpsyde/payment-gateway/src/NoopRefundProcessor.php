<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\PaymentGateway;

use WC_Order;
class NoopRefundProcessor implements RefundProcessorInterface
{
    public function refundOrderPayment(WC_Order $order, float $amount, string $reason): void
    {
    }
}