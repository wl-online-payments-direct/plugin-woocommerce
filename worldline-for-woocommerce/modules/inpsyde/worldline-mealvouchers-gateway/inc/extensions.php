<?php

declare(strict_types=1);

use Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Psr\Container\ContainerInterface;

return static function (): array {
    return [
        'payment_gateways' =>
            static function (array $gateways, ContainerInterface $container): array {
                $gateways[] = GatewayIds::MEALVOUCHERS;
                return $gateways;
            },
    ];
};
