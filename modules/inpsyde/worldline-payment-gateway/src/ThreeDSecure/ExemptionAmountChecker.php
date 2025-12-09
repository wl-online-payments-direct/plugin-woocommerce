<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure;

class ExemptionAmountChecker
{
    private int $limit;
    public function __construct(int $limit)
    {
        $this->limit = $limit;
    }
    public function isUnderLimit(int $orderAmount, string $currencyCode) : bool
    {
        if ($currencyCode !== 'EUR') {
            return \false;
        }
        return $orderAmount < $this->limit;
    }
}
