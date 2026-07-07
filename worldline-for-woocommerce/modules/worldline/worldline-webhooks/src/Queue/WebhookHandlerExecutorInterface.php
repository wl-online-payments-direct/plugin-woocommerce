<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Webhooks\Queue;

use Exception;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookHandlerExecutorInterface
{
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook) : void;
}
