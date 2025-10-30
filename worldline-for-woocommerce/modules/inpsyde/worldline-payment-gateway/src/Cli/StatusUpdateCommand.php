<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Cli;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
use Syde\Vendor\Worldline\WP_CLI;
class StatusUpdateCommand
{
    private OrderUpdater $orderUpdater;
    public function __construct(OrderUpdater $orderUpdater)
    {
        $this->orderUpdater = $orderUpdater;
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
    public function refresh(array $args) : void
    {
        $id = (int) $args[0];
        $wcOrder = \wc_get_order($id);
        if (!$wcOrder instanceof WC_Order) {
            WP_CLI::error("Order {$id} not found.");
            return;
        }
        if ($wcOrder->get_payment_method() !== GatewayIds::HOSTED_CHECKOUT) {
            WP_CLI::error("Order {$id} is not from the Worldline gateway.");
            return;
        }
        $this->orderUpdater->update(new WlopWcOrder($wcOrder));
        WP_CLI::success("Successfully updated order {$id}.");
    }
}
