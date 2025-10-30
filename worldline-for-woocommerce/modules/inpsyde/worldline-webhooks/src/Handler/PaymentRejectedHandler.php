<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
class PaymentRejectedHandler implements WebhookHandlerInterface
{
    public function accepts(WebhooksEvent $webhook) : bool
    {
        return $webhook->type === 'payment.rejected';
    }
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : void
    {
        $wlopWcOrder->addWorldlineOrderNote(\__('Payment rejected.', 'worldline-for-woocommerce'));
        $wlopWcOrder->order()->save();
    }
}
