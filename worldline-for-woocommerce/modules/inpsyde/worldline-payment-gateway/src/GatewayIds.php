<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

interface GatewayIds
{
    public const HOSTED_CHECKOUT = 'worldline-for-woocommerce';
    public const HOSTED_TOKENIZATION = 'worldline-hosted-tokenization';
    public const GOOGLE_PAY = 'worldline-google-pay';
    public const APPLE_PAY = 'worldline-apple-pay';
    public const BANK_TRANSFER = 'worldline-bank-transfer';
    public const IDEAL = 'worldline-ideal';
    public const PAYPAL = 'worldline-paypal';
    public const KLARNA_PAY_WITH_KLARNA = 'worldline-klarna-pay-with-klarna';
    public const KLARNA_PAY_NOW = 'worldline-klarna-pay-now';
    public const KLARNA_BANK_TRANSFER = 'worldline-klarna-bank-transfer';
    public const KLARNA_DIRECT_DEBIT = 'worldline-klarna-direct-debit';
    public const KLARNA_PAY_LATER = 'worldline-klarna-pay-later';
    public const KLARNA_PAY_LATER_PAY_IN_3 = 'worldline-klarna-pay-later-pay-in-3';
    public const KLARNA_PAY_LATER_BANK_TRANSFER = 'worldline-klarna-pay-later-bank-transfer';
    public const POSTFINANCE = 'worldline-postfinance';
    public const TWINT = 'worldline-twint';
    public const KLARNA_FINANCING = 'worldline-klarna-financing';
    public const KLARNA_FINANCING_PAY_IN_3 = 'worldline-klarna-financing-pay-in-3';
    public const MEALVOUCHERS = 'worldline-mealvouchers';
    public const CVCO = 'worldline-cvco';
    public const EPS = 'worldline-eps';
    public const HOSTED_CHECKOUT_GATEWAYS = [self::HOSTED_CHECKOUT, self::GOOGLE_PAY, self::APPLE_PAY, self::BANK_TRANSFER, self::IDEAL, self::PAYPAL, self::POSTFINANCE, self::MEALVOUCHERS, self::CVCO, self::EPS];
    public const ALL = [self::HOSTED_CHECKOUT, self::HOSTED_TOKENIZATION, self::GOOGLE_PAY, self::APPLE_PAY, self::BANK_TRANSFER, self::IDEAL, self::PAYPAL, self::KLARNA_PAY_WITH_KLARNA, self::KLARNA_PAY_NOW, self::KLARNA_BANK_TRANSFER, self::KLARNA_DIRECT_DEBIT, self::KLARNA_PAY_LATER, self::KLARNA_PAY_LATER_PAY_IN_3, self::KLARNA_PAY_LATER_BANK_TRANSFER, self::KLARNA_FINANCING, self::KLARNA_FINANCING_PAY_IN_3, self::POSTFINANCE, self::TWINT, self::MEALVOUCHERS, self::CVCO, self::EPS];
}
