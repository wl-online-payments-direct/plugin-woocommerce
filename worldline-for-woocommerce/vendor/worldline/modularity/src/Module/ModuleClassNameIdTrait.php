<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Modularity\Module;

trait ModuleClassNameIdTrait
{
    /**
     * @return string
     *
     * @see Module::id()
     */
    public function id() : string
    {
        return __CLASS__;
    }
}
