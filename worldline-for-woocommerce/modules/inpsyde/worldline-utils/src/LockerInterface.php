<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Utils;

interface LockerInterface
{
    public function lock(): bool;
    public function unlock(): bool;
    public function isLocked(): bool;
}
