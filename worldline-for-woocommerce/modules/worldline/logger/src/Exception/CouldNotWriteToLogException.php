<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Logger\Exception;

use Worldline\Vendor\Mockery\Exception\RuntimeException;
/**
 * To be thrown when writing to the log was failed.
 */
class CouldNotWriteToLogException extends RuntimeException implements LoggerExceptionInterface
{
}
