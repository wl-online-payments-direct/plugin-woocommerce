<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

interface AuthorizationMode
{
    public const FINAL_AUTHORIZATION = 'FINAL_AUTHORIZATION';
    public const PRE_AUTHORIZATION = 'PRE_AUTHORIZATION';
    public const SALE = 'SALE';
}
