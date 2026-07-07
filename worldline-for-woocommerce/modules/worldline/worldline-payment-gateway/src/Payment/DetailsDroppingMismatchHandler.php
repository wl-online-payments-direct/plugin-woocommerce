<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Worldline\Vendor\OnlinePayments\Sdk\Domain\Order;
class DetailsDroppingMismatchHandler implements MismatchHandlerInterface
{
    public function handle(Order $wlopOrder, \Throwable $exception) : void
    {
        $wlopOrder->setShoppingCart(null);
        $wlopOrder->setDiscount(null);
        $wlopOrder->setShipping(null);
        \do_action('wlop.payment_mismatch_error', ['exception' => $exception, 'orderId' => $wlopOrder->getReferences()->getMerchantReference()]);
    }
}
