<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

interface OrderMetaKeys
{
    public const PAYMENT_STATUS = '_wlop_payment_status';
    public const TRANSACTION_STATUS_CODE = '_wlop_transaction_status_code';
    public const TRANSACTION_ID = '_wlop_transaction_id';
    public const CREATION_TIME = '_wlop_creation_time';
    public const SAVED_TOKEN = '_wlop_saved_token';
    public const PROCESSED_WEBHOOKS = '_wlop_processed_webhooks';
    public const PENDING_REFUNDS = '_wlop_pending_refunds';
    public const MANUAL_CAPTURE_SENT = '_wlop_manual_capture';
    public const AUTO_CAPTURE_SENT = '_wlop_auto_capture';
    public const HOSTED_CHECKOUT_ID = '_wlop_hosted_checkout_id';
    public const PAYMENT_METHOD_PRODUCT_ID = '_wlop_payment_method_product_id';
    public const PAYMENT_METHOD_NAME = '_wlop_payment_method_name';
    public const PAYMENT_TOTAL_AMOUNT = '_wlop_payment_total_amount';
    public const PAYMENT_CURRENCY_CODE = '_wlop_payment_currency_code';
    public const PAYMENT_FRAUD_RESULT = '_wlop_payment_fraud_result';
    public const THREE_D_SECURE_APPLIED_EXEMPTION = '_wlop_three_d_secure_applied_exemption';
    public const THREE_D_SECURE_LIABILITY = '_wlop_three_d_secure_liability';
    public const THREE_D_SECURE_AUTHENTICATION_STATUS = '_wlop_three_d_secure_authentication_status';
    public const PAYMENT_CARD_BIN = '_wlop_payment_card_bin';
    public const PAYMENT_CARD_NUMBER = '_wlop_payment_card_number';
    public const SEPA_MANDATE_REFERENCE = '_wlop_sepa_mandate_reference';
}
