<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.ElementNameMinimalLength.TooShort
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting;

class ExpiryDate
{
    protected int $month;
    protected int $year;
    /**
     * @param int $month The month number, 1-12.
     * @param int $year The year, like 2024.
     */
    public function __construct(int $month, int $year)
    {
        $this->month = $month;
        $this->year = $year;
    }
    /**
     * Returns the month number, 1-12.
     */
    public function month() : int
    {
        return $this->month;
    }
    /**
     * Returns the year, like 2024.
     */
    public function year() : int
    {
        return $this->year;
    }
    /**
     * Returns the month in MM format.
     */
    public function mm() : string
    {
        return \str_pad((string) $this->month, 2, '0', \STR_PAD_LEFT);
    }
    /**
     * Returns ExpiryDate created from the date in MMYY format,
     * or null if the date is invalid.
     */
    public static function fromMMYY(string $mmyy) : ?ExpiryDate
    {
        if (\strlen($mmyy) !== 4) {
            return null;
        }
        $month = (int) \substr($mmyy, 0, 2);
        if ($month < 1 || $month > 12) {
            return null;
        }
        $year = (int) \substr($mmyy, 2, 2);
        if ($year < 1) {
            return null;
        }
        return new ExpiryDate($month, 2000 + $year);
    }
}
