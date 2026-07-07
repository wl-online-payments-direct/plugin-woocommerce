<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Logger\Events;

/**
 * Handler adder based on WordPress hooks.
 */
class WpHandlerAdder implements HandlerAdderInterface
{
    /**
     * @inheritDoc
     */
    public function addHandler(string $eventName, callable $handler, int $priority) : void
    {
        \add_action($eventName, $handler, $priority, 999);
    }
}
