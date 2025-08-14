<?php
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Logging;

use PHPUnit\Framework\TestCase;
use SplTempFileObject;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ConnectionResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\InvalidResponseException;

/**
 * @group logging
 */
class SplFileObjectLoggerTest extends TestCase
{
    public function testLog()
    {
        $temp = new SplTempFileObject();
        $logger = new SplFileObjectLogger($temp);
        $message = "test log";
        $logger->log($message);
        // 25 is length of DATE_ATOM
        $temp->fseek(26);
        $content = "";
        while (!$temp->eof()) {
            $content .= $temp->fgets();
        }
        $this->assertEquals($message . PHP_EOL, $content);
    }

    public function testLogException()
    {
        $temp = new SplTempFileObject();
        $logger = new SplFileObjectLogger($temp);
        $message = "test log";
        $exception = new InvalidResponseException(new ConnectionResponse(500, array(), ''));
        $logger->logException($message, $exception);
        // 25 is length of DATE_ATOM
        $temp->fseek(26);
        $content = "";
        while (!$temp->eof()) {
            $content .= $temp->fgets();
        }
        $this->assertEquals($message . PHP_EOL . $exception . PHP_EOL, $content);
    }
}
