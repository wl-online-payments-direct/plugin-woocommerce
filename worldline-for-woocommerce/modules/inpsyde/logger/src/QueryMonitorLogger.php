<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger;

use Syde\Vendor\Inpsyde\Logger\Formatter\ObjectFormatterInterface;
class QueryMonitorLogger extends AbstractLogger
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
     * @inheritDoc
     */
    protected function writeToLog(string $level, string $message, array $context = []): void
    {
        $action = $this->levelToActionName($level);
        do_action($action, $message, $context);
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
    /**
     * Translate PSR log level into an action name accepted by QueryMonitor.
     *
     * @param string $level Log level, one of {@link \Psr\Log\LogLevel::*} constants.
     *
     * @return string Action name, one of described {@link https://querymonitor.com/blog/2018/07/profiling-and-logging}.
     */
    protected function levelToActionName(string $level): string
    {
        return sprintf('qm/%1$s', $level);
    }
}
