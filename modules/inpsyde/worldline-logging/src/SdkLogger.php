<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlineLogging;

use Exception;
use Syde\Vendor\Worldline\Psr\Log\LoggerInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Logging\CommunicatorLogger;
class SdkLogger implements CommunicatorLogger
{
    protected LoggerInterface $logger;
    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }
    public function log($message) : void
    {
        if (!\is_string($message)) {
            return;
        }
        $this->logger->debug($message);
    }
    public function logException($message, Exception $exception) : void
    {
        if (!\is_string($message)) {
            return;
        }
        $this->logger->debug($message . \PHP_EOL . (string) $exception);
    }
}
