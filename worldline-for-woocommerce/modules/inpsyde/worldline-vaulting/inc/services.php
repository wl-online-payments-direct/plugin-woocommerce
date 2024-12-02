<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting\CardBinParser;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting\CardButtonRenderer;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Dhii\Services\Factories\Constructor;
return static function (): array {
    return ['vaulting.bin_parser' => new Constructor(CardBinParser::class), 'vaulting.repository.wc.tokens' => new Constructor(WcTokenRepository::class, ['worldline_payment_gateway.id', 'vaulting.bin_parser']), 'vaulting.card_button_renderer' => new Constructor(CardButtonRenderer::class)];
};
