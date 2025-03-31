<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Logger\Exception;

use Syde\Vendor\Worldline\Mockery\Exception\RuntimeException;
/**
 * To be thrown when writing to the log was failed.
 */
class CouldNotWriteToLogException extends RuntimeException implements LoggerExceptionInterface
{
}
