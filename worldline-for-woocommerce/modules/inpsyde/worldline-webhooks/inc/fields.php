<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Psr\Http\Message\UriInterface;
return new Factory(['webhooks.notification_url'], static function (?UriInterface $notificationUrl) : array {
    return ['test_webhook_id' => ['title' => \__('Test Webhook ID', 'worldline-for-woocommerce'), 'type' => 'text', 'desc_tip' => \true, 'description' => \__('Find/create the Webhook Key in the Developer tab > Webhooks on your Worldline Merchant Portal Dashboard.', 'worldline-for-woocommerce'), 'custom_attributes' => ['autocomplete' => 'off']], 'test_webhook_secret_key' => ['title' => \__('Test Secret webhook key', 'worldline-for-woocommerce'), 'type' => 'password', 'desc_tip' => \true, 'description' => \__('Find/create the Webhook Secret in the Developer tab > Webhooks on your Worldline Merchant Portal Dashboard.', 'worldline-for-woocommerce')], 'live_webhook_id' => ['title' => \__('Live Webhook ID', 'worldline-for-woocommerce'), 'type' => 'text', 'desc_tip' => \true, 'description' => \__('Find/create the Webhook Key in the Developer tab > Webhooks on your Worldline Merchant Portal Dashboard.', 'worldline-for-woocommerce'), 'custom_attributes' => ['autocomplete' => 'off']], 'live_webhook_secret_key' => ['title' => \__('Live Secret webhook key', 'worldline-for-woocommerce'), 'type' => 'password', 'desc_tip' => \true, 'description' => \__('Find/create the Webhook Secret in the Developer tab > Webhooks on your Worldline Merchant Portal Dashboard.', 'worldline-for-woocommerce')], 'webhook_endpoint_url' => ['title' => \__('Webhook endpoint', 'worldline-for-woocommerce'), 'type' => 'text', 'save' => \false, 'default' => (string) $notificationUrl, 'description' => '
                <button type="button" 
                    data-copy="
                    #woocommerce_worldline-for-woocommerce_webhook_endpoint_url
                    " 
                    data-copied-message="' . \__('Copied to clipboard', 'worldline-for-woocommerce') . '"
                    class="button-primary wlop-button-copy">' . \__('Copy', 'worldline-for-woocommerce') . '</button>', 'custom_attributes' => ['readonly' => 'readonly']]];
});
