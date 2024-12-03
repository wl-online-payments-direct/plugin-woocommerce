<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Syde\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
class AmountOfMoneyFactory
{
    private MoneyAmountConverter $moneyAmountConverter;
    public function __construct(MoneyAmountConverter $moneyAmountConverter)
    {
        $this->moneyAmountConverter = $moneyAmountConverter;
    }
    /**
     * @param WcPriceStruct $priceStruct
     * @return AmountOfMoney
     */
    public function create(WcPriceStruct $priceStruct): AmountOfMoney
    {
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($this->moneyAmountConverter->decimalValueToCentValue((float) $priceStruct->price(), $priceStruct->currency()));
        $amountOfMoney->setCurrencyCode($priceStruct->currency());
        return $amountOfMoney;
    }
}
