<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

interface GatewayIds
{
    public const HOSTED_CHECKOUT = 'worldline-for-woocommerce';
    public const HOSTED_TOKENIZATION = 'worldline-hosted-tokenization';
    public const GOOGLE_PAY = 'worldline-google-pay';
    public const APPLE_PAY = 'worldline-apple-pay';
    public const BANK_TRANSFER = 'worldline-bank-transfer';
    public const IDEAL = 'worldline-ideal';
    public const HOSTED_CHECKOUT_GATEWAYS = [self::HOSTED_CHECKOUT, self::GOOGLE_PAY, self::APPLE_PAY, self::BANK_TRANSFER, self::IDEAL];
    public const ALL = [self::HOSTED_CHECKOUT, self::HOSTED_TOKENIZATION, self::GOOGLE_PAY, self::APPLE_PAY, self::BANK_TRANSFER, self::IDEAL];
}
