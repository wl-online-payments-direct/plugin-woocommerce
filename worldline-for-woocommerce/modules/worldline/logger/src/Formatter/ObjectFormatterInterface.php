<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Logger\Formatter;

/**
 * Produces a string describing the given object for logging purposes
 */
interface ObjectFormatterInterface
{
    public function format(object $object) : string;
}
