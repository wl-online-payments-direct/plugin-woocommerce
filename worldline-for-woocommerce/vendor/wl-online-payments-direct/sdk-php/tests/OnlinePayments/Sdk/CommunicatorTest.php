<?php
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\InvalidResponseException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication\ResponseClassMap;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\DataObject;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\TestUtil\AppendingBodyHandler;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\TestUtil\TestingAuthenticator;

/**
 * @group communicator
 *
 */
class CommunicatorTest extends TestCase
{

    /** @var CommunicatorInterface */
    protected $defaultCommunicator = null;

    /** @var ResponseClassMap */
    protected $defaultResponseClassMap = null;

    public function setUp(): void
    {
        $this->skipWithoutHttpBin();

        $communicatorConfiguration = $this->getCommunicatorConfiguration();
        $communicatorConfiguration->setApiEndpoint($this->getHttpBinUrl());
        $this->defaultCommunicator = new Communicator($communicatorConfiguration, new TestingAuthenticator());
        $this->defaultResponseClassMap = new ResponseClassMap();
        $this->defaultResponseClassMap->defaultSuccessResponseClassName = '\Syde\Vendor\Worldline\OnlinePayments\Sdk\SimpleHttpBinResponse';
        $this->defaultResponseClassMap->defaultErrorResponseClassName = '\Syde\Vendor\Worldline\OnlinePayments\Sdk\TestUtil\TestErrorResponse';
    }

    public function tearDown(): void
    {
    }

    public function testApiRequestGet()
    {
        $relativeUri = '/get';
        $response = $this->defaultCommunicator->get($this->defaultResponseClassMap, $relativeUri);
        $this->assertInstanceOf('\Syde\Vendor\Worldline\OnlinePayments\Sdk\SimpleHttpBinResponse', $response);
        $this->assertEquals($this->getHttpBinUrl() . $relativeUri, $response->url);
    }

    public function testExceptionInvalidUrl()
    {
        try {
            $relativeUri = '/foo';
            $this->defaultCommunicator->get($this->defaultResponseClassMap, $relativeUri);
        } catch (InvalidResponseException $e) {
            $this->assertEquals(404, $e->getResponse()->getHttpStatusCode());
            return;
        }
        $this->fail('an expected exception has not been raised');
    }

    public function testApiRequestPost()
    {
        $relativeUri = '/post';
        $response = $this->defaultCommunicator->post($this->defaultResponseClassMap, $relativeUri);
        $this->assertInstanceOf('\Syde\Vendor\Worldline\OnlinePayments\Sdk\SimpleHttpBinResponse', $response);
        $this->assertEquals($this->getHttpBinUrl() . $relativeUri, $response->url);
    }

    public function testApiRequestPut()
    {
        $relativeUri = '/put';
        $response = $this->defaultCommunicator->put($this->defaultResponseClassMap, $relativeUri);
        $this->assertInstanceOf('\Syde\Vendor\Worldline\OnlinePayments\Sdk\SimpleHttpBinResponse', $response);
        $this->assertEquals($this->getHttpBinUrl() . $relativeUri, $response->url);
    }

    public function testApiRequestDelete()
    {
        $relativeUri = '/delete';
        $response = $this->defaultCommunicator->delete($this->defaultResponseClassMap, $relativeUri);
        $this->assertInstanceOf('\Syde\Vendor\Worldline\OnlinePayments\Sdk\SimpleHttpBinResponse', $response);
        $this->assertEquals($this->getHttpBinUrl() . $relativeUri, $response->url);
    }

    public function testApiRequestGetWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        $relativeUri = '/get';
        $this->defaultCommunicator->getWithBinaryResponse(array($bodyHandler, 'handleBodyPart'), $this->defaultResponseClassMap, $relativeUri);
        $this->assertNotEquals('', $bodyHandler->getBody());
        $this->assertStringStartsWith('{', $bodyHandler->getBody());
        $this->assertStringEndsWith('}', trim($bodyHandler->getBody()));
    }

    public function testApiRequestPostWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        $relativeUri = '/post';
        $this->defaultCommunicator->postWithBinaryResponse(array($bodyHandler, 'handleBodyPart'), $this->defaultResponseClassMap, $relativeUri);
        $this->assertNotEquals('', $bodyHandler->getBody());
        $this->assertStringStartsWith('{', $bodyHandler->getBody());
        $this->assertStringEndsWith('}', trim($bodyHandler->getBody()));
    }

    public function testApiRequestPutWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        $relativeUri = '/put';
        $this->defaultCommunicator->putWithBinaryResponse(array($bodyHandler, 'handleBodyPart'), $this->defaultResponseClassMap, $relativeUri);
        $this->assertNotEquals('', $bodyHandler->getBody());
        $this->assertStringStartsWith('{', $bodyHandler->getBody());
        $this->assertStringEndsWith('}', trim($bodyHandler->getBody()));
    }

    public function testApiRequestDeleteWithBinaryResponse()
    {
        $bodyHandler = new AppendingBodyHandler();
        $relativeUri = '/delete';
        $this->defaultCommunicator->deleteWithBinaryResponse(array($bodyHandler, 'handleBodyPart'), $this->defaultResponseClassMap, $relativeUri);
        $this->assertNotEquals('', $bodyHandler->getBody());
        $this->assertStringStartsWith('{', $bodyHandler->getBody());
        $this->assertStringEndsWith('}', trim($bodyHandler->getBody()));
    }
}

class SimpleHttpBinResponse extends DataObject
{
    public $url;

    public function toObject()
    {
        $object = parent::toObject();
        if (!is_null($this->url)) {
            $object->url = $this->url;
        }
        return $object;
    }

    public function fromObject($object)
    {
        parent::fromObject($object);
        if (property_exists($object, 'url')) {
            $this->url = $object->url;
        }
        return $this;
    }
}
