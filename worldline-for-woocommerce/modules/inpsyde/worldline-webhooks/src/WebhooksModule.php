<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use WP_REST_Request;
class WebhooksModule implements ExecutableModule, ServiceModule
{
    use ModuleClassNameIdTrait;
    /**
     * @inheritDoc
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action('rest_api_init', static function () use($container) {
            /** @var string $namespace */
            $namespace = $container->get('webhooks.namespace');
            /** @var string $route */
            $route = $container->get('webhooks.rest_route');
            /** @var string[] $methods */
            $methods = $container->get('webhooks.allowed_methods');
            /** @var callable $callback */
            $callback = $container->get('webhooks.callback');
            /** @var callable(): bool $permissionCallback */
            $permissionCallback = $container->get('webhooks.permission_callback');
            \register_rest_route($namespace, $route, ['methods' => $methods, 'callback' => $callback, 'permission_callback' => $permissionCallback]);
        });
        /** @var callable(WP_Rest_Request):void $logIncomingWebhookRequest */
        $logIncomingWebhookRequest = $container->get('webhooks.log_incoming_webhooks_request');
        \add_action('wlop.webhook_request', $logIncomingWebhookRequest);
        return \true;
    }
    /**
     * @inheritDoc
     */
    public function services() : array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
        }
        /** @var callable():
         * array<string, callable(ContainerInterface $container):mixed> $services
         */
        return $services();
    }
}
