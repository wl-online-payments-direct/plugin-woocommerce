<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Logger\Formatter;

/**
 * Delegates to one child formatter based on an array that maps
 * a formatter to a specific type of object
 */
class DelegatingObjectFormatter implements ObjectFormatterInterface
{
    /**
     * @var array<class-string,ObjectFormatterInterface>
     */
    protected $formatterMap;
    /**
     * @var ObjectFormatterInterface
     */
    protected $fallback;
    public function __construct(array $formatterMap, ObjectFormatterInterface $fallback)
    {
        $this->formatterMap = $formatterMap;
        $this->fallback = $fallback;
    }
    public function format(object $object): string
    {
        foreach ($this->formatterMap as $type => $formatter) {
            if ($object instanceof $type) {
                return $formatter->format($object);
            }
        }
        return $this->fallback->format($object);
    }
}
