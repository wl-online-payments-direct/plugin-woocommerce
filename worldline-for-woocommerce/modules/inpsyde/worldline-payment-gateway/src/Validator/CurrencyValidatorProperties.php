<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Validator;

class CurrencyValidatorProperties
{
    private string $country;
    private string $currency;
    private string $transientKey;
    public function __construct(string $country, string $currency, string $transientKey)
    {
        $this->country = $country;
        $this->currency = $currency;
        $this->transientKey = $transientKey;
    }
    public function country() : string
    {
        return $this->country;
    }
    public function currency() : string
    {
        return $this->currency;
    }
    public function transientKey() : string
    {
        return $this->transientKey;
    }
}
