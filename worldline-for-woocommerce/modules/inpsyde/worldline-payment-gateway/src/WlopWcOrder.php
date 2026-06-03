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
        $value = self::basePaymentId($value);
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
