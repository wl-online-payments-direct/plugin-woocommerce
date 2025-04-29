<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

interface OrderMetaKeys
{
    public const TRANSACTION_STATUS_CODE = '_wlop_transaction_status_code';
    public const TRANSACTION_ID = '_wlop_transaction_id';
    public const CREATION_TIME = '_wlop_creation_time';
    public const SAVED_TOKEN = '_wlop_saved_token';
    public const PROCESSED_WEBHOOKS = '_wlop_processed_webhooks';
    public const PENDING_REFUNDS = '_wlop_pending_refunds';
    public const MANUAL_CAPTURE_SENT = '_wlop_manual_capture';
    public const AUTO_CAPTURE_SENT = '_wlop_auto_capture';
    public const HOSTED_CHECKOUT_ID = '_wlop_hosted_checkout_id';
    public const THREE_D_SECURE_RESULT_PROCESSED = '_wlop_three_d_secure_result_processed';
    public const THREE_D_SECURE_APPLIED_EXEMPTION = '_wlop_three_d_secure_applied_exemption';
    public const THREE_D_SECURE_LIABILITY = '_wlop_three_d_secure_liability';
}
