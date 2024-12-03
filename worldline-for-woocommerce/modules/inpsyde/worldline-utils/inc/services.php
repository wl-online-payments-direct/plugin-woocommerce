<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Utils\FileBasedLockerFactory;
use Syde\Vendor\Psr\Container\ContainerInterface;
return static function (): array {
    return ['utils.locker.timeout' => static function (): int {
        $envTimeout = \apply_filters('wlop_locker_timeout', (int) \getenv("FILE_LOCKER_TIMEOUT"));
        if ($envTimeout) {
            return $envTimeout;
        }
        return \max(\min((int) \ini_get('max_execution_time'), 60), 30);
    }, 'utils.locker.file_based_locker_factory' => new Constructor(FileBasedLockerFactory::class, ['utils.locker.timeout', 'utils.locker.temp-dir']), 'utils.locker.temp-dir' => static function (): string {
        return (string) \get_temp_dir();
    }];
};
