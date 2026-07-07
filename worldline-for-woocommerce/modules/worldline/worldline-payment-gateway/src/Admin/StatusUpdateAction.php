<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
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
