<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk;

use Exception;
use UnexpectedValueException;
/**
 * Class Communicator
 *
 * @package OnlinePayments\Sdk
 */
class Communicator implements CommunicatorInterface
{
    const MIME_APPLICATION_JSON = 'application/json';
    /** @var Connection */
    private $connection;
    /** @var CommunicatorConfiguration */
    private $communicatorConfiguration;
    /** @var ResponseFactory|null */
    private $responseFactory = null;
    /** @var ResponseExceptionFactory|null */
    private $responseExceptionFactory = null;
    /**
     * @param Connection $connection
     * @param CommunicatorConfiguration $communicatorConfiguration
     */
    public function __construct(Connection $connection, CommunicatorConfiguration $communicatorConfiguration)
    {
        $this->connection = $connection;
        $this->communicatorConfiguration = $communicatorConfiguration;
    }
    /**
     * @param CommunicatorLogger $communicatorLogger
     */
    public function enableLogging(CommunicatorLogger $communicatorLogger)
    {
        $this->connection->enableLogging($communicatorLogger);
    }
    /**
     *
     */
    public function disableLogging()
    {
        $this->connection->disableLogging();
    }
    /**
     * @param ResponseClassMap $responseClassMap
     * @param string $relativeUriPath
     * @param string $clientMetaInfo
     * @param RequestObject|null $requestParameters
     * @param CallContext|null $callContext
     * @return DataObject
     * @throws ResponseException
     * @throws InvalidResponseException
     */
    public function get(ResponseClassMap $responseClassMap, $relativeUriPath, $clientMetaInfo = '', RequestObject $requestParameters = null, CallContext $callContext = null)
    {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        $requestHeaders = $this->getRequestHeaders('GET', $relativeUriPathWithRequestParameters, null, $clientMetaInfo, $callContext);
        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };
        $this->getConnection()->get($this->communicatorConfiguration->getApiEndpoint() . $relativeUriPathWithRequestParameters, $requestHeaders, $responseHandler, $this->communicatorConfiguration->getProxyConfiguration());
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw $this->getResponseExceptionFactory()->createException($httpStatusCode, $response, $callContext);
        }
        return $response;
    }
    /**
     * @param ResponseClassMap $responseClassMap
     * @param string $relativeUriPath
     * @param string $clientMetaInfo
     * @param RequestObject|null $requestParameters
     * @param CallContext $callContext
     * @return DataObject
     * @throws Exception
     */
    public function delete(ResponseClassMap $responseClassMap, $relativeUriPath, $clientMetaInfo = '', RequestObject $requestParameters = null, CallContext $callContext = null)
    {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        $requestHeaders = $this->getRequestHeaders('DELETE', $relativeUriPathWithRequestParameters, null, $clientMetaInfo, $callContext);
        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };
        $this->getConnection()->delete($this->communicatorConfiguration->getApiEndpoint() . $relativeUriPathWithRequestParameters, $requestHeaders, $responseHandler, $this->communicatorConfiguration->getProxyConfiguration());
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw $this->getResponseExceptionFactory()->createException($httpStatusCode, $response, $callContext);
        }
        return $response;
    }
    /**
     * @param ResponseClassMap $responseClassMap
     * @param string $relativeUriPath
     * @param string $clientMetaInfo
     * @param mixed|null $requestBodyObject
     * @param RequestObject|null $requestParameters
     * @param CallContext $callContext
     * @return DataObject
     * @throws Exception
     */
    public function post(ResponseClassMap $responseClassMap, $relativeUriPath, $clientMetaInfo = '', $requestBodyObject = null, RequestObject $requestParameters = null, CallContext $callContext = null)
    {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        if ($requestBodyObject instanceof MultipartFormDataObject) {
            $contentType = $requestBodyObject->getContentType();
            $requestBody = $requestBodyObject;
        } else if ($requestBodyObject instanceof MultipartDataObject) {
            $multipart = $requestBodyObject->toMultipartFormDataObject();
            $contentType = $multipart->getContentType();
            $requestBody = $multipart;
        } else if ($requestBodyObject instanceof DataObject || is_null($requestBodyObject)) {
            $contentType = static::MIME_APPLICATION_JSON;
            $requestBody = $requestBodyObject ? $requestBodyObject->toJson() : '';
        } else {
            throw new UnexpectedValueException('Unsupported request body');
        }
        $requestHeaders = $this->getRequestHeaders('POST', $relativeUriPathWithRequestParameters, $contentType, $clientMetaInfo, $callContext);
        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };
        $this->getConnection()->post($this->communicatorConfiguration->getApiEndpoint() . $relativeUriPathWithRequestParameters, $requestHeaders, $requestBody, $responseHandler, $this->communicatorConfiguration->getProxyConfiguration());
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw $this->getResponseExceptionFactory()->createException($httpStatusCode, $response, $callContext);
        }
        return $response;
    }
    /**
     * @param ResponseClassMap $responseClassMap
     * @param string $relativeUriPath
     * @param string $clientMetaInfo
     * @param mixed|null $requestBodyObject
     * @param RequestObject|null $requestParameters
     * @param CallContext $callContext
     * @return DataObject
     * @throws Exception
     */
    public function put(ResponseClassMap $responseClassMap, $relativeUriPath, $clientMetaInfo = '', $requestBodyObject = null, RequestObject $requestParameters = null, CallContext $callContext = null)
    {
        $relativeUriPathWithRequestParameters = $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
        if ($requestBodyObject instanceof DataObject || is_null($requestBodyObject)) {
            $contentType = static::MIME_APPLICATION_JSON;
            $requestBody = $requestBodyObject ? $requestBodyObject->toJson() : '';
        } else {
            throw new UnexpectedValueException('Unsupported request body');
        }
        $requestHeaders = $this->getRequestHeaders('PUT', $relativeUriPathWithRequestParameters, $contentType, $clientMetaInfo, $callContext);
        $responseBuilder = new ResponseBuilder();
        $responseHandler = function ($httpStatusCode, $data, $headers) use ($responseBuilder) {
            $responseBuilder->setHttpStatusCode($httpStatusCode);
            $responseBuilder->setHeaders($headers);
            $responseBuilder->appendBody($data);
        };
        $this->getConnection()->put($this->communicatorConfiguration->getApiEndpoint() . $relativeUriPathWithRequestParameters, $requestHeaders, $requestBody, $responseHandler, $this->communicatorConfiguration->getProxyConfiguration());
        $connectionResponse = $responseBuilder->getResponse();
        $this->updateCallContext($connectionResponse, $callContext);
        $response = $this->getResponseFactory()->createResponse($connectionResponse, $responseClassMap);
        $httpStatusCode = $connectionResponse->getHttpStatusCode();
        if ($httpStatusCode >= 400) {
            throw $this->getResponseExceptionFactory()->createException($httpStatusCode, $response, $callContext);
        }
        return $response;
    }
    /**
     * @param ConnectionResponse $response
     * @param CallContext $callContext
     */
    protected function updateCallContext(ConnectionResponse $response, CallContext $callContext = null)
    {
        if ($callContext) {
            $callContext->setIdempotenceRequestTimestamp($response->getHeaderValue('X-GCS-Idempotence-Request-Timestamp'));
        }
    }
    /**
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }
    /**
     * @param Connection $connection
     */
    public function setConnection(Connection $connection)
    {
        $this->connection = $connection;
    }
    /**
     * @return CommunicatorConfiguration
     */
    protected function getCommunicatorConfiguration()
    {
        return $this->communicatorConfiguration;
    }
    /**
     * @param CommunicatorConfiguration $communicatorConfiguration
     * @return Communicator
     */
    public function setCommunicatorConfiguration(CommunicatorConfiguration $communicatorConfiguration)
    {
        $this->communicatorConfiguration = $communicatorConfiguration;
        return $this;
    }
    /**
     * @param $relativeUriPath
     * @param RequestObject|null $requestParameters
     * @return string
     * @throws Exception
     */
    protected function getRequestUri($relativeUriPath, RequestObject $requestParameters = null)
    {
        return $this->communicatorConfiguration->getApiEndpoint() . $this->getRelativeUriPathWithRequestParameters($relativeUriPath, $requestParameters);
    }
    /**
     * @param string $httpMethod
     * @param string $relativeUriPathWithRequestParameters
     * @param string|null $contentType
     * @param string $clientMetaInfo
     * @param CallContext|null $callContext
     * @return string[]
     */
    protected function getRequestHeaders($httpMethod, $relativeUriPathWithRequestParameters, $contentType = null, $clientMetaInfo = '', ?CallContext $callContext = null)
    {
        $requestHeaderGenerator = new RequestHeaderGenerator($this->communicatorConfiguration, $httpMethod, $relativeUriPathWithRequestParameters, $clientMetaInfo, $callContext);
        return $requestHeaderGenerator->generateRequestHeaders($contentType);
    }
    /**
     * @param $relativeUriPath
     * @param RequestObject|null $requestParameters
     * @return string
     */
    protected function getRelativeUriPathWithRequestParameters($relativeUriPath, RequestObject $requestParameters = null)
    {
        if (is_null($requestParameters)) {
            return $relativeUriPath;
        }
        $requestParameterObjectVars = $requestParameters->toArray();
        if (count($requestParameterObjectVars) == 0) {
            return $relativeUriPath;
        }
        $httpQuery = http_build_query($requestParameterObjectVars);
        // remove [0], [1] etc that are added if properties are arrays
        $httpQuery = preg_replace('/%5B[0-9]+%5D/simU', '', $httpQuery);
        return $relativeUriPath . '?' . $httpQuery;
    }
    /** @return ResponseFactory */
    protected function getResponseFactory()
    {
        if (is_null($this->responseFactory)) {
            $this->responseFactory = new ResponseFactory();
        }
        return $this->responseFactory;
    }
    /** @return ResponseExceptionFactory */
    protected function getResponseExceptionFactory()
    {
        if (is_null($this->responseExceptionFactory)) {
            $this->responseExceptionFactory = new ResponseExceptionFactory();
        }
        return $this->responseExceptionFactory;
    }
}
