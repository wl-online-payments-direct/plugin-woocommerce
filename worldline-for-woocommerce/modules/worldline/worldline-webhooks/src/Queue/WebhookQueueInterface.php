<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Queue;

use Exception;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookQueueInterface
{
    /**
     * @throws Exception
     */
    public function add(WebhooksEvent $webhook) : void;
}
