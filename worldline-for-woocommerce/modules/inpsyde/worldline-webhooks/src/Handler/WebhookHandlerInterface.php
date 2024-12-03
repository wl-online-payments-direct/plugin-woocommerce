<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookHandlerInterface
{
    public function accepts(WebhooksEvent $webhook): bool;
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder): void;
}
