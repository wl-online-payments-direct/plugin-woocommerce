<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger\Exception;

use Syde\Vendor\Mockery\Exception\RuntimeException;
/**
 * To be thrown when writing to the log was failed.
 */
class CouldNotWriteToLogException extends RuntimeException implements LoggerExceptionInterface
{
}
