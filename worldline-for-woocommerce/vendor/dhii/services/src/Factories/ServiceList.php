<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Dhii\Services\Factories;

use Syde\Vendor\Worldline\Dhii\Services\ResolveKeysCapableTrait;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
/**
 * A service that aggregates other services into a list.
 *
 * This implementation is configured with a list of service keys. Those keys will be resolved at call-time using the
 * container, and the list of resolved values will be returned as the service value.
 *
 * Example usage:
 * ```
 * [
 *      'foo' => Value(5),
 *      'bar' => Value("hello"),
 *      'baz' => Value(1.61803),
 *
 *      'list' => new ServiceList([
 *          'foo',
 *          'bar',
 *          'baz'
 *      ]),
 * ]
 *
 * $list = $c->get('list'); // [5, "hello", 1.61803]
 * ```
 *
 * The array of service keys may also be associative. The array keys will be preserved in the result.
 *
 * ```
 * [
 *      'foo' => Value(5),
 *      'bar' => Value("hello"),
 *
 *      'config' => new ServiceList([
 *          'num' => 'foo',
 *          'msg' => 'bar'
 *      ]),
 * ]
 *
 * $list = $c->get('list'); // ['num' => 5, 'msg' => "hello"]
 * ```
 *
 */
class ServiceList extends Service
{
    use ResolveKeysCapableTrait;
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $c)
    {
        return $this->resolveDeps($c, $this->dependencies);
    }
}
