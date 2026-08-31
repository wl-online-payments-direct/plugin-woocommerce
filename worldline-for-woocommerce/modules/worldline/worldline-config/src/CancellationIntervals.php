<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Config;

interface CancellationIntervals
{
    public const ONE_HOUR = 1;
    public const THREE_HOURS = 3;
    public const SIX_HOURS = 6;
    public const TWELVE_HOURS = 12;
    public const EIGHTEEN_HOURS = 18;
    public const ONE_DAY = 24;
    /**
     * Sentinel value of the `session_timeout` dropdown meaning "use the free
     * value stored in `session_timeout_custom_value` instead of a preset".
     *
     * Deliberately a string so it can never collide with an hour preset.
     */
    public const CUSTOM = 'custom';
    public const MINUTES_PER_HOUR = 60;
    /**
     * Bounds of the custom value, in minutes. The upper bound equals ONE_DAY,
     * matching the largest preset the dropdown offers.
     */
    public const MIN_CUSTOM_MINUTES = 1;
    public const MAX_CUSTOM_MINUTES = 1440;
    public const DEFAULT_CUSTOM_MINUTES = 180;
}
