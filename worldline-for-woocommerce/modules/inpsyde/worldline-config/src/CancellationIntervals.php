<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config;

interface CancellationIntervals
{
    public const DISABLED = 0;
    public const ONE_HOUR = 1;
    public const THREE_HOURS = 3;
    public const SIX_HOURS = 6;
    public const TWELVE_HOURS = 12;
    public const EIGHTEEN_HOURS = 18;
    public const ONE_DAY = 24;
    public const TWO_DAYS = 48;
    public const THREE_DAYS = 72;
}
