<?php

declare(strict_types=1);

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;

return static function (): array {
    return [
        'payment_gateways' =>
            static function (array $gateways, ContainerInterface $container): array {
                $gateways[] = GatewayIds::MEALVOUCHERS;
                return $gateways;
            },
    ];
};
