<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Config;

interface CaptureMode
{
    public const MANUAL = 'manual';
    public const END_OF_DAY = 'end_of_day';
    public const AFTER_1D = '1d';
    public const AFTER_2D = '2d';
    public const AFTER_3D = '3d';
    public const AFTER_4D = '4d';
    public const AFTER_5D = '5d';
    public const AFTER_6D = '6d';
    public const AFTER_7D = '7d';
}
