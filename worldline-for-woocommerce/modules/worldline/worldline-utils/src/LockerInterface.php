<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Utils;

interface LockerInterface
{
    public function lock() : bool;
    /**
     * Blocks until the lock is acquired or the locker's timeout elapses.
     */
    public function lockBlocking() : bool;
    public function unlock() : bool;
    public function isLocked() : bool;
}
