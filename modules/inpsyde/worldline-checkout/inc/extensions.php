<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
return static function () : array {
    return ['return_page.payment_gateways' => static function (array $returnPagePaymentGateways, ContainerInterface $container) : array {
        return \array_merge($returnPagePaymentGateways, GatewayIds::ALL);
    }];
};
