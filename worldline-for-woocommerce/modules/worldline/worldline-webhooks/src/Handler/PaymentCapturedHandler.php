<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Handler;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
class PaymentCapturedHandler implements WebhookHandlerInterface
{
    private MoneyAmountConverter $moneyAmountConverter;
    private OrderUpdater $orderUpdater;
    public function __construct(MoneyAmountConverter $moneyAmountConverter, OrderUpdater $orderUpdater)
    {
        $this->moneyAmountConverter = $moneyAmountConverter;
        $this->orderUpdater = $orderUpdater;
    }
    public function accepts(WebhooksEvent $webhook) : bool
    {
        return $webhook->type === 'payment.captured';
    }
    /**
     * @throws \Exception
     */
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : void
    {
        $capturedAmount = WebhookHelper::paymentCapturedAmount($webhook);
        if ($capturedAmount === null) {
            throw new \Exception("Can't retrieve captured amount. Webhook: {$webhook->id}");
        }
        /*
         * The per-order lock, which is a different lock from the webhook one this
         * handler already runs under - separate namespaces, on purpose. The
         * shopper's return page writes under the per-order lock, and by the time
         * this handler runs the inner update in WebhookReceivedHandler has already
         * released it, so without this the return page could be mid-update while
         * this side completes the order: two writers, and a status transition the
         * other one had already committed applied a second time. That is another
         * "New order" and "Processing order" mail for the same payment.
         *
         * Taking the lock through lockOrder() also re-reads the order once it is
         * held, so the status guards below compare against what is in the
         * database rather than against the snapshot loaded earlier in this
         * request.
         */
        $handled = $this->orderUpdater->lockOrder($wlopWcOrder, function () use($webhook, $wlopWcOrder, $capturedAmount) : void {
            $order = $wlopWcOrder->order();
            /**
             * Explicit guard, not relying on WooCommerce core to no-op a
             * redundant payment_complete()/update_status('completed') call:
             * a concurrent/duplicate payment.captured delivery for an order
             * that's already completed must not re-trigger completion side
             * effects (e.g. a merchant's woocommerce_order_status_completed
             * hook).
             */
            if ($order->get_status() === 'completed') {
                return;
            }
            $wlopWcOrder->addWorldlineOrderNote(\sprintf(
                /* translators: %s refers to the capture amount */
                \__('Payment of %s successfully captured.', 'worldline-for-woocommerce'),
                $this->moneyAmountConverter->amountOfMoneyAsString($capturedAmount)
            ));
            $transactionId = WebhookHelper::transactionId($webhook);
            $order->payment_complete($transactionId);
            if ($order->get_status() === 'processing' && !$order->needs_processing()) {
                $order->update_status('completed');
            }
            $order->save();
        }, OrderUpdater::WEBHOOK_LOCK_WAIT_SECONDS);
        if (!$handled) {
            /*
             * Thrown rather than swallowed: the executor turns this into
             * `wlop.webhook_handler_error`, so a capture that never reached the
             * order is visible instead of silently missing.
             */
            throw new \Exception("Could not acquire the order lock, capture not applied. Webhook: {$webhook->id}");
        }
    }
}
