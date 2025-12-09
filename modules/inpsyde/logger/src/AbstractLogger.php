<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Logger;

use Syde\Vendor\Worldline\Inpsyde\Logger\Exception\InvalidLogLevelProvided;
use Syde\Vendor\Worldline\Inpsyde\Logger\Formatter\ObjectFormatterInterface;
use Syde\Vendor\Worldline\Psr\Log\AbstractLogger as PsrAbstractLogger;
use Syde\Vendor\Worldline\Psr\Log\LogLevel;
/**
 * Base functionality for loggers that use the PSR-3-based message format.
 *
 * Also adds the pre-configured "source" to context before logging.
 */
abstract class AbstractLogger extends PsrAbstractLogger
{
    public const LOG_LEVELS = [LogLevel::EMERGENCY, LogLevel::ALERT, LogLevel::CRITICAL, LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG];
    protected ObjectFormatterInterface $formatter;
    protected bool $isDebug;
    public function __construct(ObjectFormatterInterface $formatter, bool $isDebug)
    {
        $this->formatter = $formatter;
        $this->isDebug = $isDebug;
    }
    /**
     * @inheritDoc
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function log($level, $message, array $context = []) : void
    {
        // phpcs:enable
        if (!\in_array($level, self::LOG_LEVELS, \true)) {
            throw new InvalidLogLevelProvided(\sprintf('Unknown log level "%1$s"', $level));
        }
        /** @psalm-suppress RedundantCastGivenDocblockType */
        $message = (string) $message;
        $message = $this->interpolateContext($message, $context);
        $source = $this->getSource();
        if ($source) {
            $context['source'] = $source;
        }
        $version = $this->getVersion();
        if ($version) {
            $context['source'] = $context['source'] . " V-" . $version;
        }
        if ($this->isDebug || $level !== LogLevel::DEBUG) {
            $this->writeToLog($level, $message, $context);
        }
    }
    /**
     * Interpolate a context into the specified string.
     *
     * @param string $string The string to interpolate the context into.
     * @param array<string, string> $context The context to interpolate into the string.
     * @return string The string with context interpolated into it.
     */
    protected function interpolateContext(string $string, array $context = []) : string
    {
        $tokens = $this->getReplacements($context);
        return $this->interpolateTokens($string, $tokens);
    }
    /**
     * Retrieves a search and replacement map from context.
     *
     * Search tokens are based on the
     * {@link https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md#12-message PSR-3 convention}.
     *
     * @param array $context A map of context keys to context values.
     *
     * @return array A map of strings to replace - to strings to replace them with.
     */
    protected function getReplacements(array $context = []) : array
    {
        // build a replacement array with braces around the context keys
        $replace = [];
        foreach ($context as $key => $val) {
            if (!\is_string($key)) {
                continue;
            }
            if (\is_scalar($val)) {
                $replace['{' . $key . '}'] = (string) $val;
                continue;
            }
            if (\is_object($val)) {
                $replace['{' . $key . '}'] = $this->formatter->format($val);
                continue;
            }
            //TODO We're left with null, resource and array here, maybe more. Could we recurse into arrays?
        }
        return $replace;
    }
    /**
     * Replaces occurrences of tokens with their values in the specified string.
     *
     * @param string $string The string to replace the tokens in.
     * @param array<string, string> $tokens The map of token names to values.
     * @return string The string in which tokens have been replaced with their values.
     */
    protected function interpolateTokens(string $string, array $tokens = []) : string
    {
        return \strtr($string, $tokens);
    }
    /**
     * Writes the specified message to log.
     *
     * @param LogLevel::* $level The log level of the message.
     * @param string $message The message to write to the log.
     * @param array<string, string> $context The context of the message
     */
    protected abstract function writeToLog(string $level, string $message, array $context = []) : void;
    /**
     * Retrieves the name of the log source.
     *
     * @return string|null The name of the log source, if any.
     */
    protected abstract function getSource() : ?string;
    /**
     * Retrieves the plugin version.
     *
     * @return string|null The name of the plugin version, if any.
     */
    protected abstract function getVersion() : ?string;
}
