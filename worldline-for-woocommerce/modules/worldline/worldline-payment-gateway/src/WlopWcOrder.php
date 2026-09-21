<?php

declare (strict_types=1);
// phpcs:disable Worldline.CodeQuality.NoAccessors.NoSetter
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
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
    /**
     * Re-reads the wrapped order from the data store.
     *
     * Callers build this object from an order loaded before the per-order lock was
     * taken, so by the time the lock is held another request may already have
     * written a newer status. Every guard downstream reads through this object -
     * the status comparison in OrderUpdater::adjustWcStatus(), the 3DS meta
     * comparison in OrderUpdater::checkExemptionInfo() - so against a stale
     * snapshot they all conclude there is work to do and redo it: the same status
     * transition a second time, the same notes again, and a second set of
     * WooCommerce emails to the shopper.
     */
    public function refresh() : void
    {
        $orderId = $this->order->get_id();
        \wp_cache_delete($orderId, 'posts');
        $dataStore = $this->order->get_data_store();
        if ($dataStore->has_callable('clear_cached_data')) {
            $dataStore->clear_cached_data([$orderId]);
        }
        $dataStore->read($this->order);
        $this->order->read_meta_data(\true);
    }
    public function setTransactionId(string $value) : void
    {
        $value = self::basePaymentId($value);
        if ($value === $this->transactionId()) {
            return;
        }
        $this->order->update_meta_data(OrderMetaKeys::TRANSACTION_ID, $value);
        $this->order->set_transaction_id($value);
        $this->order->save();
        \do_action('wlop.transaction_id_changed', ['id' => $value, 'wcOrderId' => $this->order->get_id()]);
    }
    private static function basePaymentId(string $id) : string
    {
        if ($id === '') {
            return $id;
        }
        if (\str_contains($id, '_')) {
            return $id;
        }
        return \substr($id, 0, -3) . '000';
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
    /**
     * @return array<int, array<string, mixed>>
     */
    public function payments() : array
    {
        $raw = (string) $this->order->get_meta(OrderMetaKeys::PAYMENTS);
        if ($raw !== '') {
            $decoded = \json_decode($raw, \true);
            if (\is_array($decoded) && $decoded !== []) {
                return \array_values($decoded);
            }
        }
        if ($this->transactionId() === '') {
            return [];
        }
        return [$this->synthesizeLegacyEntry()];
    }
    /**
     * @return array<string, mixed>
     */
    private function synthesizeLegacyEntry() : array
    {
        $order = $this->order;
        $rawProductId = (string) $order->get_meta(OrderMetaKeys::PAYMENT_METHOD_PRODUCT_ID);
        $rawStatusCode = (string) $order->get_meta(OrderMetaKeys::TRANSACTION_STATUS_CODE);
        return ['paymentId' => (string) $order->get_meta(OrderMetaKeys::TRANSACTION_ID), 'productId' => $rawProductId !== '' ? (int) $rawProductId : null, 'methodName' => (string) $order->get_meta(OrderMetaKeys::PAYMENT_METHOD_NAME), 'amountCents' => (int) $order->get_meta(OrderMetaKeys::PAYMENT_TOTAL_AMOUNT), 'currency' => (string) $order->get_meta(OrderMetaKeys::PAYMENT_CURRENCY_CODE), 'statusCode' => $rawStatusCode !== '' ? (int) $rawStatusCode : null, 'status' => (string) $order->get_meta(OrderMetaKeys::PAYMENT_STATUS), 'card' => ['bin' => (string) $order->get_meta(OrderMetaKeys::PAYMENT_CARD_BIN), 'number' => (string) $order->get_meta(OrderMetaKeys::PAYMENT_CARD_NUMBER)], 'fraudResult' => (string) $order->get_meta(OrderMetaKeys::PAYMENT_FRAUD_RESULT), 'threeDS' => ['liability' => (string) $order->get_meta(OrderMetaKeys::THREE_D_SECURE_LIABILITY), 'appliedExemption' => (string) $order->get_meta(OrderMetaKeys::THREE_D_SECURE_APPLIED_EXEMPTION), 'authenticationStatus' => (string) $order->get_meta(OrderMetaKeys::THREE_D_SECURE_AUTHENTICATION_STATUS)], 'sepaMandateReference' => (string) $order->get_meta(OrderMetaKeys::SEPA_MANDATE_REFERENCE), 'capturedAmountCents' => (int) $order->get_meta(OrderMetaKeys::PAYMENT_CAPTURED_AMOUNT), 'cancelledAmountCents' => (int) $order->get_meta(OrderMetaKeys::PAYMENT_CANCELED_AMOUNT), 'pendingCaptureAmountCents' => (int) $order->get_meta(OrderMetaKeys::PAYMENT_PENDING_CAPTURE_AMOUNT), 'updatedAt' => \time()];
    }
}
