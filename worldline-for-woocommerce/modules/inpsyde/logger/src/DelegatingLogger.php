<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Logger;

use Syde\Vendor\Worldline\Psr\Log\AbstractLogger;
use Syde\Vendor\Worldline\Psr\Log\LoggerInterface;
/**
 * Logger that does not log on itself, but translates to internal loggers.
 */
class DelegatingLogger extends AbstractLogger
{
    /**
     * @var LoggerInterface[]
     */
    protected $loggers;
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
