<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Shipping;

use Exception;
use WC_Order;
use WC_Order_Query;
class AddressIndicatorHandler
{
    /**
     * @var string[]
     */
    private array $shippingFields = ['country', 'city', 'postcode', 'address_1', 'address_2', 'first_name', 'last_name'];
    /**
     * @throws Exception
     */
    public function determineAddressType(WC_Order $wcOrder) : string
    {
        if (!$wcOrder->needs_shipping_address()) {
            return 'digital-goods';
        } elseif ($this->shippingAddressSameAsBilling($wcOrder)) {
            return 'same-as-billing';
        } elseif ($this->shippingAddressIsVerified($wcOrder)) {
            return 'another-verified-address-on-file-with-merchant';
        }
        return 'different-than-billing';
    }
    protected function shippingAddressSameAsBilling(WC_Order $wcOrder) : bool
    {
        foreach ($this->shippingFields as $field) {
            if ($wcOrder->{"get_shipping_{$field}"}() !== $wcOrder->{"get_billing_{$field}"}()) {
                return \false;
            }
        }
        return \true;
    }
    /**
     * @throws Exception
     */
    protected function shippingAddressIsVerified(WC_Order $wcOrder) : bool
    {
        $customerId = $wcOrder->get_user_id();
        $orderQuery = new WC_Order_Query([
            'customer_id' => $customerId,
            // Query only completed orders. For those we can say that are verified.
            'status' => ['wc-completed'],
            'limit' => '-1',
        ]);
        $orders = $orderQuery->get_orders();
        if (!\is_array($orders)) {
            return \false;
        }
        /** @var WC_Order $order */
        foreach ($orders as $order) {
            if ($this->compareTwoOrdersShipping($order, $wcOrder)) {
                return \true;
            }
        }
        return \false;
    }
    protected function compareTwoOrdersShipping(WC_Order $order1, WC_Order $order2) : bool
    {
        if ($order1->get_id() === $order2->get_id()) {
            return \false;
        }
        foreach ($this->shippingFields as $field) {
            if ($order1->{"get_shipping_{$field}"}() !== $order2->{"get_shipping_{$field}"}()) {
                return \false;
            }
        }
        return \true;
    }
}
