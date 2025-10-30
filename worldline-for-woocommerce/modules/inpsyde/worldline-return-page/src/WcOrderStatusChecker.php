<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage;

use WC_Order;
/**
 * A simple generic implementation mapping WC order status to return page status.
 */
class WcOrderStatusChecker implements StatusCheckerInterface
{
    public function determineStatus(?WC_Order $wcOrder) : string
    {
        if (!$wcOrder) {
            throw new \Exception('WC order required.');
        }
        switch ($wcOrder->get_status()) {
            case 'on-hold':
            case 'processing':
                return ReturnPageStatus::SUCCESS;
            case 'failed':
            case 'refunded':
            case 'cancelled':
                return ReturnPageStatus::FAILED;
            default:
                return ReturnPageStatus::PENDING;
        }
    }
}
