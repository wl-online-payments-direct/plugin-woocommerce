<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Logger\Events;

/**
 * A service able to dispatch event.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event.
     *
     * @param string $eventName Non-empty string.
     * @param array $eventParams Any params in an array to be added to event.
     */
    public function dispatch(string $eventName, array $eventParams) : void;
}
