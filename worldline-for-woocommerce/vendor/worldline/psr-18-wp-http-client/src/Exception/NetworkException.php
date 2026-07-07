<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Wp\HttpClient\Exception;

use Worldline\Vendor\Psr\Http\Client\NetworkExceptionInterface;
use Worldline\Vendor\Psr\Http\Message\RequestInterface;
use Throwable;
/**
 * This exception is thrown when request cannot be done because of any network problems
 * like timeout or unreachable target
 */
class NetworkException extends WpHttpClientException implements NetworkExceptionInterface
{
    protected RequestInterface $request;
    /**
     * @param string $message Exception message
     * @param int $code Error code
     * @param Throwable|null $previous Previous exception
     * @param RequestInterface|null $request Request we tried to send
     */
    public function __construct($message = '', $code = 0, Throwable $previous = null, RequestInterface $request = null)
    {
        parent::__construct($message, $code, $previous);
        $this->request = $request;
    }
    /**
     * @inheritDoc
     */
    public function getRequest() : RequestInterface
    {
        return $this->request;
    }
}
