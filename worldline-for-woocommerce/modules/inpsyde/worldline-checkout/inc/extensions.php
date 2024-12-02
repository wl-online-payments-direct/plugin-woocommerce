<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Psr\Container\ContainerInterface;
return static function (): array {
    return ['return_page.payment_gateways' => static function (array $returnPagePaymentGateways, ContainerInterface $container): array {
        $returnPagePaymentGateways[] = $container->get('worldline_payment_gateway.id');
        return $returnPagePaymentGateways;
    }];
};
