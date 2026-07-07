<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Utils;

interface LockerFactoryInterface
{
    public function create(int $orderId) : LockerInterface;
}
