<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
interface WebhookHandlerExecutorInterface
{
    /**
     * @throws Exception
     */
    public function handle(WebhooksEvent $webhook) : void;
}
