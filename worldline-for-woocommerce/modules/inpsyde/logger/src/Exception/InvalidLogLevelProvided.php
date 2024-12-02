<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger\Exception;

use Syde\Vendor\Psr\Log\InvalidArgumentException;
/**
 * To be thrown when provided log level not listed in the Psr\Log\LogLevel class;
 */
class InvalidLogLevelProvided extends InvalidArgumentException implements LoggerExceptionInterface
{
}
