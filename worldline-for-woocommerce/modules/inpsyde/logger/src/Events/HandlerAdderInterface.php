<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger\Events;

/**
 * Service able to add listener to an event.
 */
interface HandlerAdderInterface
{
    /**
     * Add handler (listener) to the event.
     *
     * @param string $eventName Event name to add listener to.
     * @param callable $handler Handler to be called when event happened.
     * @param int $priority Determines the order of execution of handlers, lower is earlier.
     */
    public function addHandler(string $eventName, callable $handler, int $priority): void;
}
