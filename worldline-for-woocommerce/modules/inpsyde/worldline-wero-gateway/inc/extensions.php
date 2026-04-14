<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\RefundProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Payment\HostedCheckoutWeroRefundProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Payment\WeroRefundProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
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
