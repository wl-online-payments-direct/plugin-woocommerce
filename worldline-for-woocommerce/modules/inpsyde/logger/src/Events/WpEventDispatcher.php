<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger\Events;

class WpEventDispatcher implements EventDispatcherInterface
{
    /**
     * @inheritDoc
     */
    public function dispatch(string $eventName, array $eventParams): void
    {
        do_action($eventName, ...array_values($eventParams));
    }
}
