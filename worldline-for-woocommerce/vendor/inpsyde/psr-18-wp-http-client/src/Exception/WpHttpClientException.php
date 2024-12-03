<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Wp\HttpClient\Exception;

use Syde\Vendor\Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;
/**
 * General Http Client exception.
 *
 * It thrown where it's unable to send request or parse response.
 */
class WpHttpClientException extends RuntimeException implements ClientExceptionInterface
{
}
