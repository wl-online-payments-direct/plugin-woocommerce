<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
return static function () : array {
    return ['payment_gateways' => static function (array $gateways) : array {
        $gateways[] = GatewayIds::KLARNA_PAY_WITH_KLARNA;
        $gateways[] = GatewayIds::KLARNA_PAY_NOW;
        $gateways[] = GatewayIds::KLARNA_BANK_TRANSFER;
        $gateways[] = GatewayIds::KLARNA_DIRECT_DEBIT;
        $gateways[] = GatewayIds::KLARNA_PAY_LATER;
        $gateways[] = GatewayIds::KLARNA_PAY_LATER_PAY_IN_3;
        $gateways[] = GatewayIds::KLARNA_PAY_LATER_BANK_TRANSFER;
        $gateways[] = GatewayIds::KLARNA_FINANCING;
        $gateways[] = GatewayIds::KLARNA_FINANCING_PAY_IN_3;
        return $gateways;
    }];
};
