<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoSetter
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
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
    public function paymentMethodName() : string
    {
        return (string) $this->order->get_meta(OrderMetaKeys::PAYMENT_METHOD_NAME);
    }
    public function status() : string
    {
        return (string) $this->order->get_meta(OrderMetaKeys::PAYMENT_STATUS);
    }
    public function amount() : string
    {
        $amountConverter = new MoneyAmountConverter();
        $amount = $amountConverter->centValueToDecimalValue((int) $this->order->get_meta(OrderMetaKeys::PAYMENT_TOTAL_AMOUNT), $this->currencyCode());
        return \wc_price($amount, ['currency' => $this->currencyCode(), 'thousand_separator' => '']);
    }
    public function currencyCode() : string
    {
        return (string) $this->order->get_meta(OrderMetaKeys::PAYMENT_CURRENCY_CODE);
    }
    public function creditCard() : string
    {
        $bin = (string) $this->order->get_meta(OrderMetaKeys::PAYMENT_CARD_BIN);
        $number = (string) $this->order->get_meta(OrderMetaKeys::PAYMENT_CARD_NUMBER);
        if (empty($bin)) {
            return $number;
        }
        if (\substr($number, 0, \strlen($bin)) === $bin) {
            return $number;
        }
        return \substr_replace($number, $bin, 0, \strlen($bin));
    }
    public function fraudResult() : string
    {
        return \ucfirst((string) $this->order->get_meta(OrderMetaKeys::PAYMENT_FRAUD_RESULT));
    }
    public function threeDSecureLiability() : string
    {
        return \ucfirst((string) $this->order->get_meta(OrderMetaKeys::THREE_D_SECURE_LIABILITY));
    }
    public function threeDSecureExemption() : string
    {
        return \ucfirst((string) $this->order->get_meta(OrderMetaKeys::THREE_D_SECURE_APPLIED_EXEMPTION));
    }
    public function threeDSecureAuthStatus() : string
    {
        return (string) $this->order->get_meta(OrderMetaKeys::THREE_D_SECURE_AUTHENTICATION_STATUS);
    }
}
