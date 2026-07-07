<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Logger\Exception;

use Worldline\Vendor\Psr\Log\InvalidArgumentException;
/**
 * To be thrown when provided log level not listed in the Psr\Log\LogLevel class;
 */
class InvalidLogLevelProvided extends InvalidArgumentException implements LoggerExceptionInterface
{
}
