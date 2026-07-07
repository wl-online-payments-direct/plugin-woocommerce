<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Logger;

use Worldline\Vendor\Psr\Log\AbstractLogger;
use Worldline\Vendor\Psr\Log\LoggerInterface;
/**
 * Logger that does not log on itself, but translates to internal loggers.
 */
class DelegatingLogger extends AbstractLogger
{
    /**
     * @var LoggerInterface[]
     */
    protected array $loggers;
    public function __construct(LoggerInterface ...$loggers)
    {
        $this->loggers = $loggers;
    }
    /**
     * @inheritDoc
     */
    public function log($level, $message, array $context = [])
    {
        foreach ($this->loggers as $logger) {
            $logger->log($level, $message, $context);
        }
    }
}
