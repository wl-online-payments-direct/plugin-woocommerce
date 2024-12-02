<?php

namespace Syde\Vendor\Inpsyde\Logger\Formatter;

/**
 * Produces a string describing the given object for logging purposes
 */
interface ObjectFormatterInterface
{
    public function format(object $object): string;
}
