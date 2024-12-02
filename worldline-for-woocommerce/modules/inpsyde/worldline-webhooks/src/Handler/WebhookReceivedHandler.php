<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Exception;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderUpdater;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
use Syde\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
class WebhookReceivedHandler implements WebhookHandlerInterface
{
    private OrderUpdater $orderUpdater;
    public function __construct(OrderUpdater $orderUpdater)
    {
        $this->orderUpdater = $orderUpdater;
    }
    public function accepts(WebhooksEvent $webhook): bool
    {
        return !in_array($webhook->getType(), [
            // payment.created often arrives together with other webhooks
            'payment.created',
        ], \true);
    }
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder): void
    {
        $transactionId = (string) $wlopWcOrder->order()->get_meta(OrderMetaKeys::TRANSACTION_ID);
        if (!$transactionId) {
            $transactionId = WebhookHelper::transactionId($webhook);
            if (!is_null($transactionId)) {
                $wlopWcOrder->setTransactionId($transactionId);
            }
        }
        $this->orderUpdater->update($wlopWcOrder);
    }
}
