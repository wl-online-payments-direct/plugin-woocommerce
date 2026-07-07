<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Worldline\Vendor\OnlinePayments\Sdk\Domain\Order;
interface MismatchHandlerInterface
{
    public function handle(Order $wlopOrder, \Throwable $exception) : void;
}
