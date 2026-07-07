<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct;

class WcPriceStruct
{
    private string $price;
    private string $currency;
    public function __construct(string $price, string $currency)
    {
        $this->price = $price;
        $this->currency = $currency;
    }
    public function price() : string
    {
        return $this->price;
    }
    public function currency() : string
    {
        return $this->currency;
    }
}
