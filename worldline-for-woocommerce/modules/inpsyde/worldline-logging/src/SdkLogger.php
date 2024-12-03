<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlineLogging;

use Exception;
use Syde\Vendor\Psr\Log\LoggerInterface;
use Syde\Vendor\OnlinePayments\Sdk\CommunicatorLogger;
class SdkLogger implements CommunicatorLogger
{
    protected LoggerInterface $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function log($message): void
    {
        if (!is_string($message)) {
            return;
        }
        $this->logger->debug($message);
    }
    public function logException($message, Exception $exception): void
    {
        if (!is_string($message)) {
            return;
        }
        $this->logger->debug($message . \PHP_EOL . (string) $exception);
    }
}
