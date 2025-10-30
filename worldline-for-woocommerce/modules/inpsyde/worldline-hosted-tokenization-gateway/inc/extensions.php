<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Log\LogLevel;
return static function () : array {
    return ['payment_gateways' => static function (array $gateways, ContainerInterface $container) : array {
        $gateways[] = GatewayIds::HOSTED_TOKENIZATION;
        return $gateways;
    }, 'inpsyde_logger.log_events' => static function (array $previous, ContainerInterface $container) : array {
        $logEventsToAdd = [['name' => 'wlop.hosted_tokenization_payment_error', 'log_level' => LogLevel::ERROR, 'message' => 'Error encountered while creating hosted tokenization payment: {exception} Errors: {errors}'], ['name' => 'wlop.hosted_tokenization_fallback', 'log_level' => LogLevel::WARNING, 'message' => 'Hosted tokenization ID is missing, redirecting to hosted checkout page.']];
        return \array_merge($previous, $logEventsToAdd);
    }];
};
