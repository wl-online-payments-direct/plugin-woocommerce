<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Cli;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
use Syde\Vendor\WP_CLI;
class StatusUpdateCommand
{
    private OrderUpdater $orderUpdater;
    private string $gatewayId;
    public function __construct(OrderUpdater $orderUpdater, string $gatewayId)
    {
        $this->orderUpdater = $orderUpdater;
        $this->gatewayId = $gatewayId;
    }
    /**
     * Updates the order status from Worldline API.
     *
     * ## OPTIONS
     *
     * <id>
     * : The WC_Product ID
     *
     * ## EXAMPLES
     *
     *     wp wlop order refresh 42
     *
     * @when after_wp_load
     */
    public function refresh(array $args): void
    {
        $id = (int) $args[0];
        $wcOrder = wc_get_order($id);
        if (!$wcOrder instanceof WC_Order) {
            WP_CLI::error("Order {$id} not found.");
            return;
        }
        if ($wcOrder->get_payment_method() !== $this->gatewayId) {
            WP_CLI::error("Order {$id} is not from the Worldline gateway.");
            return;
        }
        $this->orderUpdater->update(new WlopWcOrder($wcOrder));
        WP_CLI::success("Successfully updated order {$id}.");
    }
}
