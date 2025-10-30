<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
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
    protected function shouldSetTransactionId(string $newTransactionId, WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : bool
    {
        $wcTransactionId = $wlopWcOrder->transactionId();
        if (!$wcTransactionId) {
            return \true;
        }
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
        if ($this->cleanupId($newTransactionId) === $this->cleanupId($newTransactionId)) {
            return \false;
        }
        return \true;
    }
    protected function cleanupId(string $id) : string
    {
        $parts = \explode('_', $id);
        if (\count($parts) !== 2) {
            // not an ID like 123456_1
            return $id;
        }
        return $parts[0];
    }
}
