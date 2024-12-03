<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger\Formatter;

class ObjectFormatter implements ObjectFormatterInterface
{
    public function format(object $object): string
    {
        if (method_exists($object, '__toString')) {
            return (string) $object;
        }
        return '';
    }
}
