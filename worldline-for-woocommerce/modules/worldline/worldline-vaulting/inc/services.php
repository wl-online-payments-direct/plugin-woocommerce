<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Dhii\Services\Factory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\CardBinParser;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\CardButtonRenderer;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Worldline\Vendor\Dhii\Services\Factories\Constructor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
return static function () : array {
    $services = ['vaulting.bin_parser' => new Constructor(CardBinParser::class), 'vaulting.card_button_renderer' => new Constructor(CardButtonRenderer::class)];
    foreach ([GatewayIds::HOSTED_CHECKOUT, GatewayIds::HOSTED_TOKENIZATION] as $gatewayId) {
        $services["vaulting.repository.wc.tokens.{$gatewayId}"] = new Factory(['vaulting.bin_parser'], static fn(CardBinParser $cardBinParser): WcTokenRepository => new WcTokenRepository($gatewayId, $cardBinParser));
    }
    return $services;
};
