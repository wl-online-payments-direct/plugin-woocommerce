<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Psr\Log\LogLevel;
// phpcs:disable Inpsyde.CodeQuality.LineLength
return static function () : array {
    return ['inpsyde_logger.log_events' => static function (array $previous) : array {
        $logEventsToAdd = [['name' => 'wlop.no_ip_address_error', 'log_level' => LogLevel::WARNING, 'message' => 'REMOTE_ADDR header is invalid or empty. Client IP address could not be determined. Check server or proxy configuration.'], ['name' => 'wlop.no_user_agent_error', 'log_level' => LogLevel::WARNING, 'message' => 'HTTP_USER_AGENT header is invalid or empty.'], ['name' => 'wlop.no_accept_error', 'log_level' => LogLevel::WARNING, 'message' => 'HTTP_ACCEPT header is invalid or empty']];
        return \array_merge($previous, $logEventsToAdd);
    }];
};
