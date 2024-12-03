<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Utils;

interface LockerFactoryInterface
{
    public function create(int $orderId): LockerInterface;
}
