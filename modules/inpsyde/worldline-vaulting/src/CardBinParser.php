<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting;

class CardBinParser
{
    /**
     * Returns the card brand like 'visa', 'mastercard', or null.
     * @param string $bin The first 6 (or more) digits of the card number.
     * @return string|null
     */
    public function detectBrand(string $bin) : ?string
    {
        if (empty($bin)) {
            return null;
        }
        // from https://github.com/chekalsky/php-banks-db/blob/5d63fa79a26ed2b6c4c8a38f9ade0a87a2d9acab/src/BankInfo.php#L43
        $cardPrefixes = [
            'electron' => '/^(4026|417500|4405|4508|4844|4913|4917)/',
            'interpayment' => '/^636/',
            'unionpay' => '/^(62|88)/',
            'discover' => '/^6(?:011|4|5)/',
            'maestro' => '/^(50|5[6-9]|6)/',
            'visa' => '/^4/',
            'mastercard' => '/^(5[1-5]|(?:222[1-9]|22[3-9][0-9]|2[3-6][0-9]{2}|27[01][0-9]|2720))/',
            // 2221-2720
            'amex' => '/^3[47]/',
            'diners' => '/^3(?:0([0-5]|95)|[689])/',
            'jcb' => '/^(?:2131|1800|(?:352[89]|35[3-8][0-9]))/',
            // 3528-3589
            'mir' => '/^220[0-4]/',
        ];
        foreach ($cardPrefixes as $brand => $regexp) {
            if (\preg_match($regexp, $bin)) {
                return $brand;
            }
        }
        return null;
    }
}
