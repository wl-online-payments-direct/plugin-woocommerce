<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Utils;

interface LockerInterface
{
    public function lock() : bool;
    public function unlock() : bool;
    public function isLocked() : bool;
}
