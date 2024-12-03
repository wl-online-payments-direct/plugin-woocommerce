<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue;

use Exception;
use Syde\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookQueueInterface
{
    /**
     * @throws Exception
     */
    public function add(WebhooksEvent $webhook): void;
}
