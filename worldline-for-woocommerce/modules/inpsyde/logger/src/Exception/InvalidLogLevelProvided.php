<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Logger\Exception;

use Syde\Vendor\Worldline\Psr\Log\InvalidArgumentException;
/**
 * To be thrown when provided log level not listed in the Psr\Log\LogLevel class;
 */
class InvalidLogLevelProvided extends InvalidArgumentException implements LoggerExceptionInterface
{
}
