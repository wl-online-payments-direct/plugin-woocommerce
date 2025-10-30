<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookHandlerInterface
{
    public function accepts(WebhooksEvent $webhook) : bool;
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder) : void;
}
