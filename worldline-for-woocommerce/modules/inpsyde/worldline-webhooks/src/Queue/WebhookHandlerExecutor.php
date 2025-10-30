<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler\WebhookHandlerInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WlopWcOrderFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
use WC_Meta_Data;
class WebhookHandlerExecutor implements WebhookHandlerExecutorInterface
{
    /**
     * @var WebhookHandlerInterface[]
     */
    private array $handlers;
    /**
     * @param WebhookHandlerInterface[] $handlers
     */
    public function __construct(array $handlers)
    {
        $this->handlers = $handlers;
    }
    public function handle(WebhooksEvent $webhook) : void
    {
        $wlopWcOrderFactory = new WlopWcOrderFactory();
        $wlopWcOrder = $wlopWcOrderFactory->create($webhook);
        if ($this->webhookProcessed($wlopWcOrder, $webhook)) {
            return;
        }
        $eventData = ['id' => $webhook->id, 'type' => $webhook->type, 'ref' => (string) WebhookHelper::reference($webhook), 'object' => $webhook];
        \do_action('wlop.webhook_handling_start', $eventData);
        foreach ($this->handlers as $handler) {
            if (!$handler->accepts($webhook)) {
                continue;
            }
            if ($this->shouldSkipMealvoucherWebhook($webhook) || $this->shouldSkipCVCOWebhook($webhook)) {
                continue;
            }
            $handlerEventData = \array_merge($eventData, ['handler' => (new \ReflectionClass($handler))->getShortName()]);
            \do_action('wlop.webhook_handler_found', $handlerEventData);
            try {
                $handler->handle($webhook, $wlopWcOrder);
            } catch (\Throwable $exception) {
                \do_action('wlop.webhook_handler_error', ['exception' => $exception]);
            }
            \do_action('wlop.webhook_handled', $handlerEventData);
        }
        $this->addProcessedWebhook($wlopWcOrder, $webhook);
        \do_action('wlop.webhook_handling_end', $eventData);
    }
    protected function wlopWebhookId(WebhooksEvent $webhook) : string
    {
        $transactionId = WebhookHelper::transactionId($webhook);
        $statusCode = (string) WebhookHelper::statusCode($webhook);
        return $webhook->type . '_' . $transactionId . '_' . $statusCode;
    }
    protected function addProcessedWebhook(WlopWcOrder $wlopWcOrder, WebhooksEvent $webhook) : void
    {
        $wlopWcOrder->order()->add_meta_data(OrderMetaKeys::PROCESSED_WEBHOOKS, $this->wlopWebhookId($webhook));
        $wlopWcOrder->order()->save();
    }
    public function webhookProcessed(WlopWcOrder $wlopWcOrder, WebhooksEvent $webhook) : bool
    {
        /** @var $webhookIdsMeta */
        $webhookIdsMeta = $wlopWcOrder->order()->get_meta(OrderMetaKeys::PROCESSED_WEBHOOKS, \false);
        if (!\is_array($webhookIdsMeta)) {
            return \false;
        }
        $processedWebhooks = \array_map(static function (WC_Meta_Data $webhookIdMeta) : string {
            return (string) $webhookIdMeta->get_data()['value'];
        }, $webhookIdsMeta);
        return \in_array($this->wlopWebhookId($webhook), $processedWebhooks, \true);
    }
    /**
     * Determines whether a Mealvouchers webhook event should be skipped.
     *
     * @param WebhooksEvent $webhook The incoming webhook event.
     *
     * @return bool True if the webhook should be skipped, false otherwise.
     */
    private function shouldSkipMealvoucherWebhook(WebhooksEvent $webhook) : bool
    {
        $type = $webhook->type;
        $mealvouchersEvents = ['payment.authorization_requested', 'payment.pending_approval', 'payment.pending_completion', 'payment.pending_capture', 'payment.captured', 'payment.cancelled', 'payment.rejected'];
        if (!\in_array($type, $mealvouchersEvents, \true)) {
            return \false;
        }
        $paymentOutput = $webhook->getPayment()->getPaymentOutput();
        $redirectOutput = $paymentOutput->getRedirectPaymentMethodSpecificOutput();
        $productId = $redirectOutput ? $redirectOutput->getPaymentProductId() : null;
        if ((int) $productId !== 5402) {
            // MEALVOCUHERS_PRODUCT_ID
            return \false;
        }
        $amount = $paymentOutput->getAmountOfMoney()->getAmount();
        $acquired = $paymentOutput->getAcquiredAmount() ? $paymentOutput->getAcquiredAmount()->getAmount() : 0;
        return $amount !== $acquired;
    }
    /**
     * Determines whether a CVCO webhook event should be skipped.
     *
     * @param WebhooksEvent $webhook The incoming webhook event.
     *
     * @return bool True if the webhook should be skipped, false otherwise.
     */
    private function shouldSkipCVCOWebhook(WebhooksEvent $webhook) : bool
    {
        $type = $webhook->type;
        $cvcoEvents = ['payment.authorization_requested', 'payment.pending_approval', 'payment.pending_completion', 'payment.pending_capture', 'payment.captured', 'payment.cancelled', 'payment.rejected'];
        if (!\in_array($type, $cvcoEvents, \true)) {
            return \false;
        }
        $paymentOutput = $webhook->getPayment()->getPaymentOutput();
        $redirectOutput = $paymentOutput->getRedirectPaymentMethodSpecificOutput();
        $productId = $redirectOutput ? $redirectOutput->getPaymentProductId() : null;
        if ((int) $productId !== 5403) {
            // CVCO_PRODUCT_ID
            return \false;
        }
        $amount = $paymentOutput->getAmountOfMoney()->getAmount();
        $acquired = $paymentOutput->getAcquiredAmount() ? $paymentOutput->getAcquiredAmount()->getAmount() : 0;
        return $amount !== $acquired;
    }
}
