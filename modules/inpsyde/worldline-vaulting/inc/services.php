<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\CardBinParser;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\CardButtonRenderer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
return static function () : array {
    $services = ['vaulting.bin_parser' => new Constructor(CardBinParser::class), 'vaulting.card_button_renderer' => new Constructor(CardButtonRenderer::class)];
    foreach ([GatewayIds::HOSTED_CHECKOUT, GatewayIds::HOSTED_TOKENIZATION] as $gatewayId) {
        $services["vaulting.repository.wc.tokens.{$gatewayId}"] = new Factory(['vaulting.bin_parser'], static fn(CardBinParser $cardBinParser): WcTokenRepository => new WcTokenRepository($gatewayId, $cardBinParser));
    }
    return $services;
};
