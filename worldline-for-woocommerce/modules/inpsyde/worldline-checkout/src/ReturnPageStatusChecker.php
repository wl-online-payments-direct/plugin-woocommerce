<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Checkout;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPageStatus;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage\WcOrderStatusChecker;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class ReturnPageStatusChecker extends WcOrderStatusChecker
{
    public function determineStatus(?WC_Order $wcOrder) : string
    {
        if (!$wcOrder) {
            throw new Exception('WC order required.');
        }
        $wlopWcOrder = new WlopWcOrder($wcOrder);
        if ($wlopWcOrder->order()->get_status() === 'failed' && $wlopWcOrder->statusCode() === 1) {
            return ReturnPageStatus::CANCELLED;
        }
        return parent::determineStatus($wcOrder);
    }
}
