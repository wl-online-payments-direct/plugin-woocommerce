<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Psr\Container\ContainerInterface;
return static function () : array {
    return ['payment_gateways' => static function (array $gateways, ContainerInterface $container) : array {
        $gateways[] = GatewayIds::MEALVOUCHERS;
        return $gateways;
    }];
};
