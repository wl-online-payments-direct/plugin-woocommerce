<?php
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\V1;

use DateTime;
use DateTimeInterface;
use ReflectionMethod;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\CallContext;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ConnectionResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communicator;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\TestCase;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\TestUtil\TestingAuthenticator;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\TestUtil\TestingConnection;

class IdempotenceTest extends TestCase
{
    public function testRequestHeadersWithIdempotenceKey()
    {
        $callContext = new CallContext();
        $idempotenceKey = 'test';
        $idempotenceKeyHeaderName = 'X-GCS-Idempotence-Key';
        $requestHeaders = $this->getRequestHeaders($callContext);
        $this->assertArrayNotHasKey($idempotenceKeyHeaderName, $requestHeaders);
        $callContext->setIdempotenceKey('test');
        $requestHeaders = $this->getRequestHeaders($callContext);
        $this->assertArrayHasKey($idempotenceKeyHeaderName, $requestHeaders);
        $this->assertEquals($idempotenceKey, $requestHeaders[$idempotenceKeyHeaderName]);
    }

    public function testGetWithCallContext()
    {
        $callContext = new CallContext();
        $idempotenceKey = 'test';
        $idempotenceRequestTimestamp = '12345';
        $idempotenceResponseDateTime = new DateTime('2023-10-10T08:00:00Z');
        $callContext->setIdempotenceKey($idempotenceKey);
        $responseHeaders = array(
            'X-GCS-Idempotence-Request-Timestamp' => $idempotenceRequestTimestamp,
            'IdempotencyResponseDatetime' => $idempotenceResponseDateTime->format(DateTimeInterface::ATOM),
            'Content-Type' => 'application/json'
        );
        $responseBody = "{}";
        $response = new ConnectionResponse(201, $responseHeaders, $responseBody);
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicator = new Communicator($communicatorConfiguration, new TestingAuthenticator(), new TestingConnection($response));
        $communicator->get(new ResponseClassMap(), '/', '', null, $callContext);
        $this->assertEquals($idempotenceKey, $callContext->getIdempotenceKey());
        $this->assertEquals($idempotenceRequestTimestamp, $callContext->getIdempotenceRequestTimestamp());
        $this->assertEquals($idempotenceResponseDateTime, $callContext->getIdempotenceResponseDateTime());
    }

    private function getRequestHeaders(CallContext $context) {
        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicator = new Communicator($communicatorConfiguration, new TestingAuthenticator());
        $method = new ReflectionMethod($communicator, 'getRequestHeaders');
        $method->setAccessible(true);
        return $method->invoke($communicator, 'GET', 'https://example.com', '', '', $context);
    }
}
