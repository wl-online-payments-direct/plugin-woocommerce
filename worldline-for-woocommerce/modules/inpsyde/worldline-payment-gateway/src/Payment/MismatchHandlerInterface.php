<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Syde\Vendor\OnlinePayments\Sdk\Domain\Order;
interface MismatchHandlerInterface
{
    public function handle(Order $wlopOrder, \Throwable $exception): void;
}
