<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Exception;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
class PaymentRejectedHandler implements WebhookHandlerInterface
{
    public function accepts(WebhooksEvent $webhook): bool
    {
        return $webhook->getType() === 'payment.rejected';
    }
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder): void
    {
        $wlopWcOrder->addWorldlineOrderNote(__('Payment rejected.', 'worldline-for-woocommerce'));
        $wlopWcOrder->order()->save();
    }
}
