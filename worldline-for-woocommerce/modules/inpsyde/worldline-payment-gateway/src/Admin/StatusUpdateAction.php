<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class StatusUpdateAction
{
    private OrderUpdater $orderUpdater;
    private string $gatewayId;
    public function __construct(OrderUpdater $orderUpdater, string $gatewayId)
    {
        $this->orderUpdater = $orderUpdater;
        $this->gatewayId = $gatewayId;
    }
    public function isAvailable(WC_Order $wcOrder): bool
    {
        return $wcOrder->get_payment_method() === $this->gatewayId;
    }
    public function render(array $orderActions, WC_Order $wcOrder): array
    {
        if (!$this->isAvailable($wcOrder)) {
            return $orderActions;
        }
        $orderActions['worldline_update_order_status'] = esc_html__('Refresh Worldline status', 'worldline-for-woocommerce');
        return $orderActions;
    }
    public function execute(WC_Order $wcOrder): void
    {
        $this->orderUpdater->update(new WlopWcOrder($wcOrder));
    }
}
