<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Handler;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookHandlerInterface
{
    public function accepts(WebhooksEvent $webhook) : bool;
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : void;
}
