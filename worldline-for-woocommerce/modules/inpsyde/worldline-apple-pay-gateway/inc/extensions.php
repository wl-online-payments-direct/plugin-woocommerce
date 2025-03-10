<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
return static function (): array {
    return ['payment_gateways' => static function (array $gateways): array {
        $gateways[] = GatewayIds::APPLE_PAY;
        return $gateways;
    }];
};
