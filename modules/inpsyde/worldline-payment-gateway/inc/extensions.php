<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Log\LogLevel;
return static function () : array {
    return ['payment_gateways' => static function (array $gateways, ContainerInterface $container) : array {
        $gateways[] = GatewayIds::HOSTED_CHECKOUT;
        return $gateways;
    }, 'inpsyde_logger.log_events' => static function (array $previous, ContainerInterface $container) : array {
        $logEventsToAdd = [['name' => 'wlop.hosted_payment_error', 'log_level' => LogLevel::ERROR, 'message' => 'Error encountered while retrieving hosted checkout URL during checkout process: {exception} Errors: {errors}']];
        return \array_merge($previous, $logEventsToAdd);
    }];
};
