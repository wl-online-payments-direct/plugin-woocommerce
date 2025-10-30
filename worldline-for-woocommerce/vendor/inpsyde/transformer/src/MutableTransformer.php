<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Transformer;

use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\TransformerException;
interface MutableTransformer extends Transformer
{
    /**
     * Pushes a transformer function to the transformer stack.
     * The given $transformer MUST specify a return type and
     * provide typehints for its payload parameter.
     * Another transformer with the same signature MUST overwrite the existing one.
     *
     * This is a valid transformer function signature:
     *
     * function(string $timeStamp):DateTime{
     *   return new DateTime($timeStamp);
     * }
     *
     * @param callable|Closure(mixed):mixed $transformer
     *
     * @return mixed
     * @throws TransformerException
     */
    public function addTransformer(callable $transformer) : self;
    /**
     * Pushes a transformer extension function to the extension stack.
     * The callback signature follows a similar structure like the $transformer of addTransformer().
     * The optional $priority SHOULD be used for allowing the extension
     * to take precedence over other extensions. Lower priority means earlier execution.
     *
     * @param callable $middleware
     * @param int $priority Controls which place in the queue this extension takes
     *
     * @return mixed
     * @throws TransformerException
     */
    public function addMiddleware(callable $middleware, int $priority = 50) : self;
}
