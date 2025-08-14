<?php

declare(strict_types=1);

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;

return static function (): array {
    return [
        'payment_gateways' =>
            static function (array $gateways): array {
                $gateways[] = GatewayIds::CVCO;
                return $gateways;
            },
    ];
};
