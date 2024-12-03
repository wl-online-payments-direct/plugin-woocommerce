<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

interface OrderMetaKeys
{
    public const TRANSACTION_STATUS_CODE = '_wlop_transaction_status_code';
    public const TRANSACTION_ID = '_wlop_transaction_id';
    public const SAVED_TOKEN = '_wlop_saved_token';
    public const PROCESSED_WEBHOOKS = '_wlop_processed_webhooks';
    public const PENDING_REFUNDS = '_wlop_pending_refunds';
    public const MANUAL_CAPTURE_SENT = '_wlop_manual_capture';
    public const HOSTED_CHECKOUT_ID = '_wlop_hosted_checkout_id';
}
