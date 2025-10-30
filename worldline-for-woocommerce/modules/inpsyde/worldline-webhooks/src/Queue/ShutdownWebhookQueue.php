<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
class ShutdownWebhookQueue implements WebhookQueueInterface
{
    protected WebhookHandlerExecutorInterface $executor;
    /**
     * @var WebhooksEvent[]
     */
    protected array $webhooks = [];
    protected bool $hookRegistered = \false;
    public function __construct(WebhookHandlerExecutorInterface $executor)
    {
        $this->executor = $executor;
    }
    public function add(WebhooksEvent $webhook) : void
    {
        $this->webhooks[$webhook->id] = $webhook;
        $this->registerShutdownHandler();
    }
    protected function registerShutdownHandler() : void
    {
        if ($this->hookRegistered) {
            return;
        }
        \add_action('shutdown', function () : void {
            foreach ($this->webhooks as $webhook) {
                $this->executor->handle($webhook);
            }
            $this->webhooks = [];
        }, -100);
        $this->hookRegistered = \true;
    }
}
