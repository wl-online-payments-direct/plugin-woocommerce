<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Dhii\Services\Extensions;

use Syde\Vendor\Worldline\Dhii\Services\ResolveKeysCapableTrait;
use Syde\Vendor\Worldline\Dhii\Services\Service;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
/**
 * An extension implementation that extends an array service.
 *
 * This implementation is configured with a list of service keys. These service keys will be resolved at call-time using
 * the container, and the resolved list of service values will be merged with the original service's value.
 *
 * Note: This implementation uses {@link array_merge()} to extend the original array service. This means that positional
 * entries are not overwritten, but associative entries are.
 *
 * Example usage:
 *  ```
 *  // Factories
 *  [
 *      'menu_links' => new Value([]),
 *
 *      'home_link' => new Value('Home'),
 *      'blog_link' => new Value('Blog'),
 *      'about_link' => new Value('About Us'),
 *  ]
 *
 *  // Extensions
 *  [
 *      'menu_links' => new ArrayExtension([
 *          'home_link',
 *          'blog_link',
 *          'about_link',
 *      ]),
 *  ]
 *  ```
 *
 */
class ArrayExtension extends Service
{
    use ResolveKeysCapableTrait;
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $c, array $prev = [])
    {
        return array_merge($prev, $this->resolveDeps($c, $this->dependencies));
    }
}
