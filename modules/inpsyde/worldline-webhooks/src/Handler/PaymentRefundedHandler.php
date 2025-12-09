<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
use WC_Meta_Data;
use WC_Order;
use WC_Order_Item_Fee;
use WC_Order_Item_Product;
use WC_Order_Item_Shipping;
class PaymentRefundedHandler implements WebhookHandlerInterface
{
    private MoneyAmountConverter $moneyAmountConverter;
    public function __construct(MoneyAmountConverter $moneyAmountConverter)
    {
        $this->moneyAmountConverter = $moneyAmountConverter;
    }
    public function accepts(WebhooksEvent $webhook) : bool
    {
        return \in_array($webhook->type, ['payment.refunded', 'payment.cancelled'], \true);
    }
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : void
    {
        if ($webhook->type === 'payment.cancelled') {
            // cancelled during checkout
            if ($wlopWcOrder->statusCode() === 1 || WebhookHelper::statusCode($webhook) === 1) {
                return;
            }
        }
        try {
            $this->issueRefund($webhook, $wlopWcOrder);
        } catch (\Throwable $exception) {
            /* translators: 1 Worldline transaction ID */
            $wlopWcOrder->addWorldlineOrderNote(\sprintf(\__('Failed to issue a refund in the WooCommerce. Transaction ID: %s', 'worldline-for-woocommerce'), WebhookHelper::transactionIdForUi($webhook)));
            \do_action('wlop.refund_wc_error', ['exception' => $exception, 'orderId' => $wlopWcOrder->order()->get_id()]);
        }
    }
    /**
     * Returns the list of items for the wc_create_refund data,
     * making all items refunded (max qty, total, taxes).
     */
    protected function refundItems(WC_Order $wcOrder) : array
    {
        $refundedItems = [];
        foreach ($wcOrder->get_items(['line_item', 'fee', 'shipping']) as $item) {
            // some methods like get_taxes() are not defined in WC_Order_Item
            if (!$item instanceof WC_Order_Item_Product && !$item instanceof WC_Order_Item_Fee && !$item instanceof WC_Order_Item_Shipping) {
                continue;
            }
            $taxes = [];
            $itemTaxes = $item->get_taxes();
            if (\is_array($itemTaxes) && isset($itemTaxes['total'])) {
                $taxes = $itemTaxes['total'];
            }
            $refundedItems[$item->get_id()] = ['qty' => $item->get_type() === 'line_item' ? $item->get_quantity() : 0, 'refund_total' => $item->get_total(), 'refund_tax' => $taxes];
        }
        return $refundedItems;
    }
    /**
     * Returns the previously saved WC refund data or null.
     */
    protected function findPendingRefundData(float $refundValue, WlopWcOrder $wlopWcOrder) : ?array
    {
        $pendingRefunds = $wlopWcOrder->order()->get_meta(OrderMetaKeys::PENDING_REFUNDS, \false);
        if (!\is_array($pendingRefunds)) {
            return null;
        }
        // sort descending by time in case there are multiple refunds (failed etc.)
        $getTime = static fn(WC_Meta_Data $wcmd): int => $wcmd->value['wlop_created_time'];
        \usort($pendingRefunds, static fn(WC_Meta_Data $md1, WC_Meta_Data $md2): int => $getTime($md2) <=> $getTime($md1));
        foreach ($pendingRefunds as $refund) {
            $amount = (float) $refund->value['amount'];
            if (\abs($amount - $refundValue) < 1.0E-5) {
                $result = $refund->value;
                $wlopWcOrder->order()->delete_meta_data_by_mid((int) $refund->id);
                return $result;
            }
        }
        return null;
    }
    /**
     * Returns the config for wc_create_refund.
     */
    protected function makeRefundData(float $refundValue, string $reason, WlopWcOrder $wlopWcOrder) : array
    {
        $refundData = ['amount' => $refundValue, 'reason' => $reason, 'order_id' => $wlopWcOrder->order()->get_id(), 'line_items' => [], 'refund_payment' => \false, 'restock_items' => (bool) \apply_filters('wlop_restock_refunded_items', \true)];
        $remainingAmount = (float) $wlopWcOrder->order()->get_remaining_refund_amount();
        // try to use the saved data from WC
        $pendingRefundData = $this->findPendingRefundData($refundValue, $wlopWcOrder);
        if ($pendingRefundData !== null) {
            $refundData['line_items'] = $pendingRefundData['line_items'];
            $refundData['restock_items'] = $pendingRefundData['restock_items'];
            $refundData['reason'] = \implode(' | ', \array_filter([$pendingRefundData['reason'], $refundData['reason']]));
        } elseif ($remainingAmount <= $refundValue && $remainingAmount === (float) $wlopWcOrder->order()->get_total()) {
            $refundData['line_items'] = $this->refundItems($wlopWcOrder->order());
        }
        return $refundData;
    }
    protected function makeReasonText(WebhooksEvent $webhook) : string
    {
        $reason = '';
        if ($webhook->type === 'payment.refunded') {
            $reason = \__('Refund processed.', 'worldline-for-woocommerce');
        } elseif ($webhook->type === 'payment.cancelled') {
            $reason = \__('Authorization cancelled.', 'worldline-for-woocommerce');
        }
        return (string) \sprintf(
            /* translators: %s transaction ID */
            \__('%1$s Worldline transaction ID: %2$s', 'worldline-for-woocommerce'),
            $reason,
            WebhookHelper::transactionIdForUi($webhook)
        );
    }
    /**
     * @throws Exception
     */
    protected function issueRefund(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : void
    {
        $refundAmountOfMoney = WebhookHelper::paymentRefundedAmount($webhook);
        if (\is_null($refundAmountOfMoney)) {
            throw new Exception('Refund amount does not exist in the received webhook object.');
        }
        $refundValue = $this->moneyAmountConverter->centValueToDecimalValue($refundAmountOfMoney->getAmount(), $refundAmountOfMoney->getCurrencyCode());
        $refundData = $this->makeRefundData($refundValue, $this->makeReasonText($webhook), $wlopWcOrder);
        $refund = \wc_create_refund($refundData);
        if ($refund instanceof \WP_Error) {
            throw new Exception(' Amount: ' . (string) $refundValue . ', ' . $refund->get_error_message());
        }
        $note = '';
        if ($webhook->type === 'payment.refunded') {
            /* translators: 1 Amount of money */
            $note = \__('%s was refunded.', 'worldline-for-woocommerce');
        } elseif ($webhook->type === 'payment.cancelled') {
            /* translators: 1 Amount of money */
            $note = \__('%s was cancelled.', 'worldline-for-woocommerce');
        }
        $wlopWcOrder->addWorldlineOrderNote(\sprintf($note, \wc_price($refundValue)));
        $wlopWcOrder->order()->save();
        \do_action('wlop.refund_wc_success', ['orderId' => $wlopWcOrder->order()->get_id(), 'amount' => $this->moneyAmountConverter->centValueToDecimalValue($refundAmountOfMoney->getAmount(), $refundAmountOfMoney->getCurrencyCode())]);
    }
}
