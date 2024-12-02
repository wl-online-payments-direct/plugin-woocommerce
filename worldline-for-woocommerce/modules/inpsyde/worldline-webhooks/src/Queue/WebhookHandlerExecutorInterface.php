<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookHandlerExecutorInterface
{
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook): void;
}
