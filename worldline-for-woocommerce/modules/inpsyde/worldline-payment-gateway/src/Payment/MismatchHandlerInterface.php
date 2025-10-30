<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Order;
interface MismatchHandlerInterface
{
    public function handle(Order $wlopOrder, \Throwable $exception) : void;
}
