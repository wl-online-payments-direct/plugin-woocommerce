<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Checkout;

use Exception;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage\StatusUpdaterInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class ReturnPageStatusUpdater implements StatusUpdaterInterface
{
    private OrderUpdater $orderUpdater;
    public function __construct(OrderUpdater $orderUpdater)
    {
        $this->orderUpdater = $orderUpdater;
    }
    public function updateStatus(?WC_Order $wcOrder) : void
    {
        if (!$wcOrder) {
            throw new Exception('WC order required.');
        }
        // Interactive path: the shopper is waiting on the return page, so wait
        // briefly for a concurrent writer instead of dropping this update.
        $this->orderUpdater->update(new WlopWcOrder($wcOrder), OrderUpdater::INTERACTIVE_LOCK_WAIT_SECONDS);
    }
}
