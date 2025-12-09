<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Controller;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler\WebhookHandlerInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue\WebhookQueueInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\WebhookEventFactory;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Webhooks\SignatureValidationException;
use Throwable;
use WP_REST_Request;
use WP_REST_Response;
/**
 * The main webhook controller. It handles request first and decides what to do next:
 * give it to another controller or return response immediately.
 */
class WorldlineWebhooksController implements WpRestApiControllerInterface
{
    private WebhookEventFactory $webhookEventFactory;
    private WebhookQueueInterface $queue;
    public function __construct(WebhookEventFactory $webhookEventFactory, WebhookQueueInterface $queue)
    {
        $this->webhookEventFactory = $webhookEventFactory;
        $this->queue = $queue;
    }
    /**
     * @inheritDoc
     */
    public function handleWpRestRequest(WP_REST_Request $request) : WP_REST_Response
    {
        $response = new WP_REST_Response(null, 200);
        try {
            \do_action('wlop.webhook_request', $request);
            try {
                $webhookEvent = $this->webhookEventFactory->fromRequest($request->get_body(), $request->get_headers());
                \do_action('wlop.webhook_event', ['id' => $webhookEvent->id, 'type' => $webhookEvent->type, 'ref' => (string) WebhookHelper::reference($webhookEvent), 'object' => $webhookEvent]);
                $this->queue->add($webhookEvent);
            } catch (SignatureValidationException $exception) {
                \do_action('wlop.webhook_verification_failed', ['exception' => $exception]);
            }
            \do_action('wlop.webhook_response', $response);
        } catch (Throwable $error) {
            \do_action('wlop.webhook_error', ['exception' => $error]);
        }
        return $response;
    }
}
