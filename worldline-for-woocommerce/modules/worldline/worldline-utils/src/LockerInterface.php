<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Utils;

interface LockerInterface
{
    public function lock() : bool;
    public function unlock() : bool;
    public function isLocked() : bool;
}
