<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Handler;

use Exception;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
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
