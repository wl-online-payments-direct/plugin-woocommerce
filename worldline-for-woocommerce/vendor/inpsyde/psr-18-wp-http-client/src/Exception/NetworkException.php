<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Wp\HttpClient\Exception;

use Syde\Vendor\Psr\Http\Client\NetworkExceptionInterface;
use Syde\Vendor\Psr\Http\Message\RequestInterface;
use Throwable;
/**
 * This exception is thrown when request cannot be done because of any network problems
 * like timeout or unreachable target
 */
class NetworkException extends WpHttpClientException implements NetworkExceptionInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;
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
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
