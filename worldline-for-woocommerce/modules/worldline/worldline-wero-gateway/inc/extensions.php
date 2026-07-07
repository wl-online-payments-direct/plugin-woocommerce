<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Worldline\PaymentGateway\RefundProcessorInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WeroGateway\Payment\HostedCheckoutWeroRefundProcessor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WeroGateway\Payment\WeroRefundProcessor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Psr\Container\ContainerInterface;
return static function () : array {
    return ['payment_gateways' => static function (array $gateways) : array {
        $gateways[] = GatewayIds::WERO;
        return $gateways;
    }, 'payment_gateway.worldline-for-woocommerce.refund_processor' => static function (RefundProcessorInterface $previous, ContainerInterface $container) : HostedCheckoutWeroRefundProcessor {
        /** @var WeroRefundProcessor $weroRefundProcessor */
        $weroRefundProcessor = $container->get('payment_gateway.' . GatewayIds::WERO . '.refund_processor');
        return new HostedCheckoutWeroRefundProcessor($previous, $weroRefundProcessor);
    }];
};
