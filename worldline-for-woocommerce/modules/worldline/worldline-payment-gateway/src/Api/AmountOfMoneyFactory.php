<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
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
    public function create(WcPriceStruct $priceStruct) : AmountOfMoney
    {
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($this->moneyAmountConverter->decimalValueToCentValue((float) $priceStruct->price(), $priceStruct->currency()));
        $amountOfMoney->setCurrencyCode($priceStruct->currency());
        return $amountOfMoney;
    }
}
