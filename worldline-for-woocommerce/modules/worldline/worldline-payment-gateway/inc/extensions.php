<?php

declare (strict_types=1);
namespace Worldline\Vendor;

// phpcs:disable Worldline.CodeQuality.LineLength
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Log\LogLevel;
return static function () : array {
    return ['payment_gateways' => static function (array $gateways, ContainerInterface $container) : array {
        $gateways[] = GatewayIds::HOSTED_CHECKOUT;
        return $gateways;
    }, 'worldline_logger.log_events' => static function (array $previous, ContainerInterface $container) : array {
        $logEventsToAdd = [['name' => 'wlop.hosted_payment_error', 'log_level' => LogLevel::ERROR, 'message' => 'Error encountered while retrieving hosted checkout URL during checkout process: {exception} Errors: {errors}']];
        return \array_merge($previous, $logEventsToAdd);
    }];
};
