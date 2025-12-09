<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config\ConfigContainer;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Dhii\Services\Factories\ServiceList;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Value;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Controller\WorldlineWebhooksController;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Controller\WpRestApiControllerInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler\PaymentCapturedHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler\PaymentRefundedHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler\PaymentRejectedHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler\WebhookReceivedHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\LogIncomingWebhookRequest;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue\ShutdownWebhookQueue;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Queue\WebhookHandlerExecutor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\WebhookEventFactory;
return static function () : array {
    $moduleRoot = \dirname(__DIR__);
    return [
        'webhooks.module_root_path' => new Value($moduleRoot),
        'webhooks.namespace' => new Alias('core.webhooks.namespace'),
        // Real permission check happens later, when the request is processed
        'webhooks.permission_callback' => new Value('__return_true'),
        'webhooks.callback' => new Factory(['webhooks.controller.webhooks_controller'], static function (WpRestApiControllerInterface $restApiController) : callable {
            return static function (\WP_REST_Request $request) use($restApiController) : \WP_REST_Response {
                return $restApiController->handleWpRestRequest($request);
            };
        }),
        'webhooks.log_incoming_webhooks_request' => new Constructor(LogIncomingWebhookRequest::class, ['webhooks.security_header_names']),
        'webhooks.factory.webhook_event' => new Constructor(WebhookEventFactory::class, ['webhooks.webhook_id', 'webhooks.webhook_secret_key']),
        'webhooks.controller.webhooks_controller' => new Constructor(WorldlineWebhooksController::class, ['webhooks.factory.webhook_event', 'webhooks.queue']),
        'webhooks.rest_route' => new Alias('core.webhooks.route'),
        'webhooks.allowed_methods' => static function () : array {
            return [\WP_REST_Server::CREATABLE];
        },
        'webhooks.webhook_id' => new Factory(['webhooks.test_webhook_id', 'webhooks.live_webhook_id', 'config.is_live'], static function (string $testWebhookId, string $liveWebhookId, bool $isLive) : string {
            return $isLive ? $liveWebhookId : $testWebhookId;
        }),
        'webhooks.webhook_secret_key' => new Factory(['webhooks.test_webhook_secret_key', 'webhooks.live_webhook_secret_key', 'config.is_live'], static function (string $testWebhookSecretKey, string $liveWebhookSecretKey, bool $isLive) : string {
            return $isLive ? $liveWebhookSecretKey : $testWebhookSecretKey;
        }),
        'webhooks.test_webhook_id' => new Factory(['config.container'], static function (ConfigContainer $config) : string {
            return (string) $config->get('test_webhook_id');
        }),
        'webhooks.test_webhook_secret_key' => new Factory(['config.container'], static function (ConfigContainer $config) : string {
            return (string) $config->get('test_webhook_secret_key');
        }),
        'webhooks.live_webhook_id' => new Factory(['config.container'], static function (ConfigContainer $config) : string {
            return (string) $config->get('live_webhook_id');
        }),
        'webhooks.live_webhook_secret_key' => new Factory(['config.container'], static function (ConfigContainer $config) : string {
            return (string) $config->get('live_webhook_secret_key');
        }),
        'webhooks.notification_url' => new Alias('core.webhooks.notification_url'),
        'webhooks.settings.fields' => Service::fromFile("{$moduleRoot}/inc/fields.php"),
        'webhooks.security_header_names' => new Value(['x_gcs_signature', 'x_gcs_keyid']),
        'webhooks.queue' => new Alias('webhooks.queue.shutdown'),
        'webhooks.queue.shutdown' => new Constructor(ShutdownWebhookQueue::class, ['webhooks.queue.executor']),
        'webhooks.queue.executor' => new Constructor(WebhookHandlerExecutor::class, ['webhooks.handlers']),
        'webhooks.handlers' => new ServiceList(['webhooks.handlers.webhook_received', 'webhooks.handlers.payment_captured', 'webhooks.handlers.payment_refunded', 'webhooks.handlers.payment_rejected']),
        'webhooks.handlers.payment_captured' => new Constructor(PaymentCapturedHandler::class, ['worldline_payment_gateway.money_amount_converter']),
        'webhooks.handlers.webhook_received' => new Constructor(WebhookReceivedHandler::class, ['worldline_payment_gateway.order_updater']),
        'webhooks.handlers.payment_refunded' => new Constructor(PaymentRefundedHandler::class, ['worldline_payment_gateway.money_amount_converter']),
        'webhooks.handlers.payment_rejected' => new Constructor(PaymentRejectedHandler::class),
    ];
};
