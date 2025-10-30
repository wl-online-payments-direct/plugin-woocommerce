<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength
use Syde\Vendor\Worldline\Inpsyde\Modularity\Package;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Properties\PluginProperties;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Log\LoggerInterface;
use Syde\Vendor\Worldline\Psr\Log\LogLevel;
use Syde\Vendor\Worldline\Psr\Log\NullLogger;
return static function () : array {
    return ['inpsyde_logger.logger' => static function (LoggerInterface $previousLogger, ContainerInterface $container) : LoggerInterface {
        if (!$container->get('core.is_logging_enabled')) {
            return new NullLogger();
        }
        /** @var LoggerInterface */
        return $container->get('inpsyde_logger.wc_logger');
    }, 'inpsyde_logger.logging_source' => static function (string $previous, ContainerInterface $container) : string {
        /** @var PluginProperties $pluginProperties */
        $pluginProperties = $container->get(Package::PROPERTIES);
        return $pluginProperties->name();
    }, 'inpsyde_logger.log_events' => static function (array $previous, ContainerInterface $container) : array {
        $logEventsToAdd = [['name' => 'wlop.auth_error', 'log_level' => LogLevel::ERROR, 'message' => 'Connection to the Worldline API failed. {exception}'], ['name' => 'wlop.webhook_verification_failed', 'log_level' => LogLevel::ERROR, 'message' => 'Webhook verification failed. {exception}'], ['name' => 'wlop.webhook_handler_error', 'log_level' => LogLevel::ERROR, 'message' => 'Webhook handler failed: {exception}'], ['name' => 'wlop.webhook_event', 'log_level' => LogLevel::INFO, 'message' => 'Received {type} webhook {id} (WC order {ref}).'], ['name' => 'wlop.wc_order_status_updated', 'log_level' => LogLevel::INFO, 'message' => 'WC order {wcOrderId} set to {status} (Worldline status {statusCode}).'], ['name' => 'wlop.unexpected_status_code', 'log_level' => LogLevel::WARNING, 'message' => 'Unexpected Worldline status code {statusCode} for WC order {wcOrderId}.'], ['name' => 'wlop.refund_wc_error', 'log_level' => LogLevel::ERROR, 'message' => 'WC refund error, order ID: {orderId}, {exception}'], ['name' => 'wlop.refund_wc_success', 'log_level' => LogLevel::INFO, 'message' => 'Refund has been issued successfully. Order ID: {orderId}, Amount: {amount}'], ['name' => 'wlop.admin_capture_error', 'log_level' => LogLevel::ERROR, 'message' => 'Unable to capture funds from the WC admin. {exception}'], ['name' => 'wlop.admin_refund_error', 'log_level' => LogLevel::ERROR, 'message' => 'Unable to issue a refund from the WC admin. {exception}'], ['name' => 'wlop.card_token_saved', 'log_level' => LogLevel::INFO, 'message' => 'Card token {token} (Worldline product {paymentProductId}) was saved for customer {userId}.'], ['name' => 'wlop.card_token_get_info_error', 'log_level' => LogLevel::WARNING, 'message' => 'Failed to request info about the card token {token} for user {userId}. Reason: {exception}.'], ['name' => 'wlop.card_token_delete_error', 'log_level' => LogLevel::ERROR, 'message' => 'The deletion of card token {token} for user {userId} was unsuccessful. Reason: {exception}.'], ['name' => 'wlop.payment_products_error', 'log_level' => LogLevel::ERROR, 'message' => 'Unable to get Worldline products. {exception}'], ['name' => 'wlop.payment_mismatch_error', 'log_level' => LogLevel::WARNING, 'message' => 'Payment mismatch error. Payment details for order {orderId} dropped. {exception}'], ['name' => 'wlop.incoming_request_data', 'log_level' => LogLevel::DEBUG, 'message' => 'Incoming webhook arrived.' . \PHP_EOL . 'HTTP method: {method}. Query params: {queryParams}.' . \PHP_EOL . 'Body content: {bodyContents}.' . \PHP_EOL . 'Headers: {headers}.'], ['name' => 'wlop.webhook_handler_found', 'log_level' => LogLevel::DEBUG, 'message' => '{handler}: Starting handling of {type} webhook {id} (WC order {ref}).'], ['name' => 'wlop.webhook_handled', 'log_level' => LogLevel::DEBUG, 'message' => '{handler}: Handled {type} webhook {id} (WC order {ref}).'], ['name' => 'wlop.card_token_already_exists', 'log_level' => LogLevel::DEBUG, 'message' => 'Card token {token} (Worldline product {paymentProductId}) is already saved for customer {userId}.'], ['name' => 'wlop.transaction_id_changed', 'log_level' => LogLevel::INFO, 'message' => 'The transaction ID for order {wcOrderId} was set to {id}.']];
        return \array_merge($previous, $logEventsToAdd);
    }];
};
