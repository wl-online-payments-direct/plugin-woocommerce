<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Psr\Container\ContainerInterface;
return static function (): array {
    return ['payment_gateways' => static function (array $gateways, ContainerInterface $container): array {
        $gateways[] = GatewayIds::GOOGLE_PAY;
        return $gateways;
    }];
};
