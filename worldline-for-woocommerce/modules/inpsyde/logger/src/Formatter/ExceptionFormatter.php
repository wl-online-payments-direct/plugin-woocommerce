<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger\Formatter;

use Throwable;
class ExceptionFormatter implements ObjectFormatterInterface
{
    /**
     * @var bool
     */
    private $logExceptionBacktrace;
    public function __construct(bool $logExceptionBacktrace)
    {
        $this->logExceptionBacktrace = $logExceptionBacktrace;
    }
    /**
     * Produces a recursive exception trace.
     * If WP_DEBUG is active, the full trace is used, otherwise a shorter on will be produced
     *
     * @param object $object
     *
     * @return string
     */
    public function format(object $object): string
    {
        assert($object instanceof Throwable);
        $messages = [];
        do {
            $previous = $object->getPrevious();
            $messages[] = $this->formatSingleException($object);
            $object = $previous ?: $object;
        } while ($previous instanceof Throwable);
        return $this->formatMessages($messages);
    }
    public function formatSingleException(Throwable $throwable): string
    {
        return $this->logExceptionBacktrace ? $this->formatFullExceptionTrace($throwable) : $this->formatShortExceptionTrace($throwable);
    }
    /**
     * Recursively formats the exception and all its ascendant into a short error message
     *
     * @param Throwable $exception
     *
     * @return string
     */
    private function formatShortExceptionTrace(Throwable $exception): string
    {
        return sprintf('%1$s: %2$s in %3$s:%4$d', get_class($exception), $exception->getMessage(), $exception->getFile(), $exception->getLine());
    }
    /**
     * Returns the full exception trace by string casting
     *
     * @param Throwable $exception
     *
     * @return string
     */
    private function formatFullExceptionTrace(Throwable $exception): string
    {
        return sprintf('%1$s%2$s%3$s', $this->formatShortExceptionTrace($exception), \PHP_EOL, $exception->getTraceAsString());
    }
    /**
     * Combine messages into a single string.
     *
     * @param string[] $messages
     * @return string
     */
    protected function formatMessages(array $messages): string
    {
        return implode(\PHP_EOL . 'Previous:' . \PHP_EOL, $messages);
    }
}
