<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Logger\Formatter;

class ObjectFormatter implements ObjectFormatterInterface
{
    public function format(object $object) : string
    {
        if (\method_exists($object, '__toString')) {
            return (string) $object;
        }
        return '';
    }
}
