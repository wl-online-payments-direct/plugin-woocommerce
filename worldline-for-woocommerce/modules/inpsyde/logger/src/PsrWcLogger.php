<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger;

use Exception;
use Syde\Vendor\Inpsyde\Logger\Formatter\ObjectFormatterInterface;
use Syde\Vendor\Psr\Log\LogLevel;
use WC_Logger_Interface;
/**
 * PSR-3 wrapper for a WooCommerce logger.
 */
class PsrWcLogger extends AbstractLogger
{
    /**
     * @var WC_Logger_Interface
     */
    protected $logger;
    /**
     * @var string|null The source of logs for WC.
     */
    protected $source;
    /**
     * @var string|null The version of the plugin logged.
     */
    protected $version;
    /**
     * PsrWcLogger constructor.
     * @param WC_Logger_Interface $logger WooCommerce logger instance
     * @param string|null $source The source of logs for WC (displayed with WC_Log_Handler_DB),
     * such as "MyProject".
     * If null, the default WC behavior is used (the name of this file).
     */
    public function __construct(ObjectFormatterInterface $formatter, WC_Logger_Interface $logger, ?string $source, ?string $version, bool $isDebug)
    {
        $this->logger = $logger;
        $this->source = $source;
        $this->version = $version;
        parent::__construct($formatter, $isDebug);
    }
    /**
     * Writes the specified string to log.
     *
     * @param LogLevel::* $level The log level of the message.
     * @param string $message The string to write to the log.
     * @param array<string, string> $context The context to log with.
     *
     * @throws Exception If problem writing.
     */
    protected function writeToLog(string $level, string $message, array $context = []): void
    {
        $this->logger->log($level, $message, $context);
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
