<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Modularity\Module;

/**
 * @package Worldline\Modularity\Module
 */
interface Module
{
    /**
     * Unique identifier for your Module.
     *
     * @return string
     */
    public function id() : string;
}
