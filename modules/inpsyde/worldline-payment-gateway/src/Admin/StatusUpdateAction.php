<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class StatusUpdateAction
{
    private OrderUpdater $orderUpdater;
    public function __construct(OrderUpdater $orderUpdater)
    {
        $this->orderUpdater = $orderUpdater;
    }
    public function isAvailable(WC_Order $wcOrder) : bool
    {
        return \in_array($wcOrder->get_payment_method(), GatewayIds::ALL, \true);
    }
    public function render(array $orderActions, WC_Order $wcOrder) : array
    {
        if (!$this->isAvailable($wcOrder)) {
            return $orderActions;
        }
        $orderActions['worldline_update_order_status'] = \esc_html__('Refresh Worldline status', 'worldline-for-woocommerce');
        return $orderActions;
    }
    public function execute(WC_Order $wcOrder) : void
    {
        $this->orderUpdater->update(new WlopWcOrder($wcOrder));
    }
}
