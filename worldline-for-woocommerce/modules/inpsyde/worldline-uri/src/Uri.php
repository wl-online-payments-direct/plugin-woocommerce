<?php

// phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uri;

use InvalidArgumentException;
use Syde\Vendor\Worldline\Psr\Http\Message\UriInterface;
use RuntimeException;
use UnexpectedValueException;
/**
 * PSR-7 URI implementation.
 *
 * Based on {@link https://github.com/Nyholm/psr7/blob/master/src/Uri.php Uri}.
 *
 */
class Uri implements UriInterface
{
    use RegexTrait;
    protected const SCHEMES = ['http' => 80, 'https' => 443, 'ftp' => 21, 'ssh' => 22, 'mysql' => 3306, 'smtp' => 25];
    protected const CHAR_UNRESERVED = 'a-zA-Z0-9_\\-\\.~';
    protected const CHAR_SUB_DELIMS = '!\\$&\'\\(\\)\\*\\+,;=';
    /** @var int */
    protected const MAX_PORT = 65535;
    /** @var int */
    protected const MIN_PORT = 0;
    protected ?string $scheme = null;
    protected ?string $user = null;
    protected ?string $host = null;
    protected ?int $port = null;
    protected ?string $path = null;
    protected ?string $query = null;
    protected ?string $fragment = null;
    protected ?string $password = null;
    public function __construct(?string $scheme, ?string $user, ?string $password, ?string $host, ?int $port, ?string $path, ?string $query, ?string $fragment)
    {
        $this->scheme = $scheme;
        $this->user = $user;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->query = $query;
        $this->fragment = $fragment;
    }
    /**
     * @inheritDoc
     */
    public function __toString() : string
    {
        return $this->createUriString((string) $this->scheme, $this->getAuthority(), (string) $this->path, (string) $this->query, (string) $this->fragment);
    }
    /**
     * @inheritDoc
     */
    public function getScheme() : string
    {
        return (string) $this->scheme;
    }
    /**
     * @inheritDoc
     */
    public function getAuthority() : string
    {
        $host = $this->host;
        if (\is_null($host)) {
            return '';
        }
        $authority = $host;
        $userInfo = $this->getUserInfo();
        if (!empty($userInfo)) {
            $authority = "{$userInfo}@{$authority}";
        }
        $port = $this->getPort();
        if (!\is_null($port) && !$this->isStandardPort((string) $this->scheme, $port)) {
            $authority = "{$authority}:{$port}";
        }
        return $authority;
    }
    /**
     * @inheritDoc
     */
    public function getUserInfo() : string
    {
        $userInfo = '';
        if (\is_null($this->user)) {
            return $userInfo;
        }
        $userInfo = $this->user;
        if (!\is_null($this->password)) {
            $userInfo .= ":{$this->password}";
        }
        return $userInfo;
    }
    /**
     * @inheritDoc
     */
    public function getHost() : string
    {
        return (string) $this->host;
    }
    /**
     * @inheritDoc
     */
    public function getPort() : ?int
    {
        return $this->port;
    }
    /**
     * @inheritDoc
     */
    public function getPath() : string
    {
        return (string) $this->path;
    }
    /**
     * @inheritDoc
     */
    public function getQuery() : string
    {
        return (string) $this->query;
    }
    /**
     * @inheritDoc
     */
    public function getFragment() : string
    {
        return (string) $this->fragment;
    }
    /**
     * @inheritDoc
     */
    public function withScheme($scheme) : self
    {
        /**
         * @psalm-suppress DocblockTypeContradiction
         * @psalm-suppress TypeDoesNotContainType
         */
        if (!\is_string($scheme)) {
            throw new InvalidArgumentException('Scheme must be a string');
        }
        $scheme = \trim($scheme);
        $scheme = \strtolower($scheme);
        $new = clone $this;
        $new->scheme = $scheme;
        return $new;
    }
    /**
     * @inheritDoc
     */
    public function withUserInfo($user, $password = null) : self
    {
        $user = $user === '' ? null : $user;
        $new = clone $this;
        $new->user = $user;
        $new->password = $password;
        return $new;
    }
    /**
     * @inheritDoc
     */
    public function withHost($host) : self
    {
        $host = \trim($host);
        $host = \strtolower($host);
        $new = clone $this;
        $new->host = $host;
        return $new;
    }
    /**
     * @inheritDoc
     */
    public function withPort($port) : self
    {
        $port = $this->normalizePort($port);
        $new = clone $this;
        $new->port = $port;
        return $new;
    }
    /**
     * @inheritDoc
     */
    public function withPath($path) : self
    {
        $path = $this->normalizePath($path);
        $new = clone $this;
        $new->path = $path;
        return $new;
    }
    /**
     * @inheritDoc
     */
    public function withQuery($query) : self
    {
        $query = $this->normalizeQueryAndFragment($query);
        $new = clone $this;
        $new->query = $query;
        return $new;
    }
    /**
     * @inheritDoc
     */
    public function withFragment($fragment) : self
    {
        $fragment = $this->normalizeQueryAndFragment($fragment);
        $new = clone $this;
        $new->fragment = $fragment;
        return $new;
    }
    // phpcs:disable Inpsyde.CodeQuality.NestingLevel
    // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
    /**
     * Create a URI string from its various parts.
     */
    protected function createUriString(string $scheme, string $authority, string $path, string $query, string $fragment) : string
    {
        $uri = '';
        if (!empty($scheme)) {
            $uri .= "{$scheme}:";
        }
        if (!empty($authority)) {
            $uri .= "//{$authority}";
        }
        if (!empty($path)) {
            $char0 = \substr($path, 0, 1) !== \false ? \substr($path, 0, 1) : null;
            $char1 = \substr($path, 1, 1) !== \false ? \substr($path, 1, 1) : null;
            if ($char0 !== '/') {
                if (!empty($authority)) {
                    // If the path is rootless and an authority is present, the path MUST be prefixed by "/"
                    $path = "/{$path}";
                }
            } elseif ($char1 === '/') {
                if (empty($authority)) {
                    // If the path is starting with more than one "/" and no authority is present, the
                    // starting slashes MUST be reduced to one.
                    $path = \ltrim($path, '/');
                    $path = "/{$path}";
                }
            }
            $uri .= $path;
        }
        if (!empty($query)) {
            $uri .= "?{$query}";
        }
        if (!empty($fragment)) {
            $uri .= "#{$fragment}";
        }
        return $uri;
    }
    // phpcs:enable Inpsyde.CodeQuality.NestingLevel
    // phpcs:enable Generic.Metrics.CyclomaticComplexity.TooHigh
    /**
     * Determines whether a port is standard for a scheme.
     *
     * @param string $scheme The scheme.
     * @param int $port The port number.
     *
     * @return bool True if the specified port is standard for the specified scheme;
     *              false otherwise.
     *
     * @throws RuntimeException If problem determining.
     */
    protected function isStandardPort(string $scheme, int $port) : bool
    {
        return isset(self::SCHEMES[$scheme]) && $port === self::SCHEMES[$scheme];
    }
    /**
     * Normalizes a port.
     *
     * @param int|null|mixed $port The port to normalize.
     *
     * @return int|null The port.
     */
    private function normalizePort($port) : ?int
    {
        if ($port === null) {
            return $port;
        }
        $maxPort = static::MAX_PORT;
        $minPort = static::MIN_PORT;
        $port = (int) $port;
        if ($port < static::MIN_PORT || $port > $maxPort) {
            throw new InvalidArgumentException(\sprintf('Invalid port "%1$d". Must be between %2$d and %3$d', $port, $minPort, $maxPort));
        }
        return $port;
    }
    /**
     * Normalizes a string according to the rules of URL path part.
     *
     * @param string|mixed $path The string to normalize.
     *
     * @return string The normalized string.
     * @throws InvalidArgumentException If string could not be normalized.
     * @throws RuntimeException If problem normalizing.
     */
    protected function normalizePath($path) : string
    {
        if (!\is_string($path)) {
            throw new InvalidArgumentException('Path must be a string');
        }
        $path = \trim($path);
        return $this->pregReplaceCallback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\\/]++|%(?![A-Fa-f0-9]{2}))/', static function (array $match) : string {
            if (!isset($match[0])) {
                throw new UnexpectedValueException('Replacement callback received no matches');
            }
            return \rawurlencode((string) $match[0]);
        }, $path);
    }
    /**
     * Normalizes a string according to the rules of URL query and fragment parts.
     *
     * @param string|mixed $str The string to normalize.
     *
     * @return string The normalized string.
     * @throws InvalidArgumentException If string could not be normalized.
     * @throws RuntimeException If problem normalizing.
     */
    protected function normalizeQueryAndFragment($str) : string
    {
        if (!\is_string($str)) {
            throw new InvalidArgumentException('Query and fragment must be a string');
        }
        $str = \trim($str);
        return $this->pregReplaceCallback('/(?:[^' . self::CHAR_UNRESERVED . self::CHAR_SUB_DELIMS . '%:@\\/\\?]++|%(?![A-Fa-f0-9]{2}))/', static function (array $match) : string {
            if (!isset($match[0])) {
                throw new UnexpectedValueException('Replacement callback received no matches');
            }
            return \rawurlencode((string) $match[0]);
        }, $str);
    }
}
