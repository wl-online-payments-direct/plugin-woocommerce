<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Handler;

use Exception;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
class WebhookReceivedHandler implements WebhookHandlerInterface
{
    private OrderUpdater $orderUpdater;
    public function __construct(OrderUpdater $orderUpdater)
    {
        $this->orderUpdater = $orderUpdater;
    }
    public function accepts(WebhooksEvent $webhook) : bool
    {
        return !\in_array($webhook->type, [
            // payment.created often arrives together with other webhooks
            'payment.created',
        ], \true);
    }
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : void
    {
        $transactionId = WebhookHelper::transactionId($webhook);
        if (!\is_null($transactionId) && $this->shouldSetTransactionId($transactionId, $webhook, $wlopWcOrder)) {
            $wlopWcOrder->setTransactionId($transactionId);
        }
        $this->orderUpdater->update($wlopWcOrder);
    }
    /**
     * Whether this webhook's payment id should become the order's transaction id.
     *
     * The unsuccessful/refunded check runs before the empty-id check on purpose.
     * An empty stored id used to mean "accept anything", but `initWlopWcOrder()`
     * empties it at the start of every attempt, so a late webhook for the previous,
     * abandoned payment would be adopted mid-retry - and the order would then be
     * refreshed from that cancelled payment and driven back to `failed`.
     *
     * Rejecting it costs nothing: `OrderUpdater::refreshWlopData()` falls back to
     * the stored hosted checkout id, which points at the attempt actually in
     * progress, and sets the transaction id from there.
     */
    protected function shouldSetTransactionId(string $newTransactionId, WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : bool
    {
        $payment = $webhook->getPayment();
        if (!$payment) {
            return \false;
        }
        $statusOutput = $payment->getStatusOutput();
        if (!$statusOutput) {
            return \false;
        }
        $statusCategory = $statusOutput->getStatusCategory();
        if (\in_array($statusCategory, ['UNSUCCESSFUL', 'REFUNDED'], \true)) {
            return \false;
        }
        $wcTransactionId = $wlopWcOrder->transactionId();
        if (!$wcTransactionId) {
            return \true;
        }
        if (WebhookHelper::cleanupId($newTransactionId) === WebhookHelper::cleanupId($wcTransactionId)) {
            return \false;
        }
        return \true;
    }
}
