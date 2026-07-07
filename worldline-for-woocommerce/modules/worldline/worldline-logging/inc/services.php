<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Dhii\Services\Factories\Constructor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlineLogging\SdkLogger;
use Worldline\Vendor\Psr\Container\ContainerInterface;
return static function () : array {
    return ['worldline_logging.sdk_logger' => new Constructor(SdkLogger::class, ['worldline_logger.logger']), 'worldline_logger.is_debug' => static function (ContainerInterface $container) : bool {
        return $container->get('core.is_debug_logging_enabled');
    }];
};
