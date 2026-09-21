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
        /*
         * Interactive path: the shopper is waiting on the return page, so wait
         * briefly for a concurrent writer instead of dropping this update.
         *
         * This is the AJAX poll, not the page load, so it gets the full interactive
         * budget rather than the short page-load one. The page load has already
         * emptied the cart and written the session by the time this runs, so there
         * is no stale cart snapshot being held open while we wait here.
         */
        $this->orderUpdater->update(new WlopWcOrder($wcOrder), OrderUpdater::INTERACTIVE_LOCK_WAIT_SECONDS);
    }
}
