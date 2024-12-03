<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger;

use Syde\Vendor\Inpsyde\Logger\Exception\CouldNotWriteToLogException;
use Syde\Vendor\Inpsyde\Logger\Formatter\ObjectFormatterInterface;
/**
 * A logger that uses PHP's native {@see error_log()}.
 */
class NativePhpLogger extends AbstractLogger
{
    /** @var string|null */
    protected $source;
    /**
     * @var string|null
     */
    protected $version;
    public function __construct(ObjectFormatterInterface $formatter, ?string $source, ?string $version, bool $isDebug)
    {
        $this->source = $source;
        $this->version = $version;
        parent::__construct($formatter, $isDebug);
    }
    /**
     * Writes the specified message to log.
     *
     * @param string $level The log level of the message.
     * @param string $message The message to write to the log.
     * @param array<string, string> $context The context of the message
     *
     * @throws CouldNotWriteToLogException
     */
    protected function writeToLog(string $level, string $message, array $context = []): void
    {
        $level = strtoupper($level);
        $entry = "{$level}: {$message}";
        // phpcs:ignore WordPress.PHP.DevelopmentFunctions
        $success = error_log($entry);
        if (!$success) {
            throw new CouldNotWriteToLogException('Failed writing to the log. Please, check your file permissions and logging settings.');
        }
    }
    /**
     * @inheritDoc
     */
    protected function getSource(): ?string
    {
        return $this->source;
    }
    /**
     * @inheritDoc
     */
    protected function getVersion(): ?string
    {
        return $this->version;
    }
}
