<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Psr\Log\LogLevel;
// phpcs:disable Worldline.CodeQuality.LineLength
return static function () : array {
    return ['worldline_logger.log_events' => static function (array $previous) : array {
        $logEventsToAdd = [['name' => 'wlop.no_ip_address_error', 'log_level' => LogLevel::WARNING, 'message' => 'REMOTE_ADDR header is invalid or empty. Client IP address could not be determined. Check server or proxy configuration.'], ['name' => 'wlop.no_user_agent_error', 'log_level' => LogLevel::WARNING, 'message' => 'HTTP_USER_AGENT header is invalid or empty.'], ['name' => 'wlop.no_accept_error', 'log_level' => LogLevel::WARNING, 'message' => 'HTTP_ACCEPT header is invalid or empty']];
        return \array_merge($previous, $logEventsToAdd);
    }];
};
