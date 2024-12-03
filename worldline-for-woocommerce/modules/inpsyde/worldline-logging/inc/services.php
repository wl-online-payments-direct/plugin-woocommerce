<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlineLogging\SdkLogger;
use Syde\Vendor\Psr\Container\ContainerInterface;
return static function (): array {
    return ['worldline_logging.sdk_logger' => new Constructor(SdkLogger::class, ['inpsyde_logger.logger']), 'inpsyde_logger.is_debug' => static function (ContainerInterface $container): bool {
        return $container->get('core.is_debug_logging_enabled');
    }];
};
