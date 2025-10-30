<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Utils\FileBasedLockerFactory;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function () : array {
    return ['utils.locker.timeout' => static function () : int {
        $envTimeout = \apply_filters('wlop_locker_timeout', (int) \getenv("FILE_LOCKER_TIMEOUT"));
        if ($envTimeout) {
            return $envTimeout;
        }
        return \max(\min((int) \ini_get('max_execution_time'), 60), 30);
    }, 'utils.locker.file_based_locker_factory' => new Constructor(FileBasedLockerFactory::class, ['utils.locker.timeout', 'utils.locker.temp-dir']), 'utils.locker.temp-dir' => static function () : string {
        return (string) \get_temp_dir();
    }, 'utils.client_ip_address' => static function () : ?string {
        if (isset($_SERVER['REMOTE_ADDR'])) {
            $serverIp = \filter_var(\wp_unslash($_SERVER['REMOTE_ADDR']), \FILTER_VALIDATE_IP);
            if ($serverIp) {
                return $serverIp;
            }
        }
        \do_action('wlop.no_ip_address_error');
        return null;
    }, 'utils.client_user_agent' => static function () : ?string {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $userAgent = \sanitize_text_field(\wp_unslash($_SERVER['HTTP_USER_AGENT']));
            if ($userAgent) {
                return $userAgent;
            }
        }
        \do_action('wlop.no_user_agent_error');
        return null;
    }, 'utils.client_accept' => static function () : ?string {
        if (isset($_SERVER['HTTP_ACCEPT'])) {
            $accept = \sanitize_text_field(\wp_unslash($_SERVER['HTTP_ACCEPT']));
            if ($accept) {
                return $accept;
            }
        }
        \do_action('wlop.no_accept_error');
        return null;
    }];
};
