<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoSetter
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

use WC_Order;
class WlopWcOrder
{
    protected WC_Order $order;
    public function __construct(WC_Order $wcOrder)
    {
        $this->order = $wcOrder;
    }
    /**
     * @param mixed $note
     * @return void
     */
    public function addWorldlineOrderNote($note) : void
    {
        $this->order->add_order_note('Worldline: ' . $note);
    }
    public function order() : WC_Order
    {
        return $this->order;
    }
    public function setTransactionId(string $value) : void
    {
        $this->order->update_meta_data(OrderMetaKeys::TRANSACTION_ID, $value);
        $this->order->set_transaction_id($value);
        $this->order->save();
        \do_action('wlop.transaction_id_changed', ['id' => $value, 'wcOrderId' => $this->order->get_id()]);
    }
    public function transactionId() : string
    {
        return (string) $this->order->get_meta(OrderMetaKeys::TRANSACTION_ID);
    }
    public function hostedCheckoutId() : string
    {
        return (string) $this->order->get_meta(OrderMetaKeys::HOSTED_CHECKOUT_ID);
    }
    public function statusCode() : int
    {
        return (int) $this->order->get_meta(OrderMetaKeys::TRANSACTION_STATUS_CODE);
    }
}
