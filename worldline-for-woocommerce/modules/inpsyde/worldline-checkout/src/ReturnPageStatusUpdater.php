<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Checkout;

use Exception;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage\StatusUpdaterInterface;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class ReturnPageStatusUpdater implements StatusUpdaterInterface
{
    private OrderUpdater $orderUpdater;
    public function __construct(OrderUpdater $orderUpdater)
    {
        $this->orderUpdater = $orderUpdater;
    }
    public function updateStatus(?WC_Order $wcOrder): void
    {
        if (!$wcOrder) {
            throw new Exception('WC order required.');
        }
        $this->orderUpdater->update(new WlopWcOrder($wcOrder));
    }
}
