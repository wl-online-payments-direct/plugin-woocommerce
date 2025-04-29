<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure;

interface ExemptionType
{
    public const LOW_VALUE = 'low-value';
    public const TRA = 'transaction-risk-analysis';
}
