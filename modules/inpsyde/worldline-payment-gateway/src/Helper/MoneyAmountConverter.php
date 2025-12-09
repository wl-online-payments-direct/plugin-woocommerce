<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
class MoneyAmountConverter
{
    public function centValueToDecimalValue(int $centValue, string $currency) : float
    {
        return $centValue / $this->centDecimalConversionFactor($currency);
    }
    public function decimalValueToCentValue(float $decimalValue, string $currency) : int
    {
        return (int) \round($decimalValue * (float) $this->centDecimalConversionFactor($currency));
    }
    public function amountOfMoneyAsString(AmountOfMoney $amountOfMoney) : string
    {
        return \wc_price($this->centValueToDecimalValue($amountOfMoney->getAmount(), $amountOfMoney->getCurrencyCode()), ['currency' => $amountOfMoney->getCurrencyCode()]);
    }
    /* This method is created for future currency support and serves as an example.
     * We could use default factor of 100 and fill the array only with currencies
     * that have a different factor than 100.
     */
    private function centDecimalConversionFactor(string $currency) : int
    {
        $currencyFactors = ['EUR' => 100, 'AUD' => 100];
        if (!isset($currencyFactors[$currency])) {
            return 100;
        }
        return $currencyFactors[$currency];
    }
}
