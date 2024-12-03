<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
class PaymentCaptureValidator
{
    private string $wlopPaymentGatewayId;
    public function __construct(string $wlopPaymentGatewayId)
    {
        $this->wlopPaymentGatewayId = $wlopPaymentGatewayId;
    }
    public function validate(\WC_Order $wcOrder): bool
    {
        return $wcOrder->get_status() === 'on-hold' && $wcOrder->get_payment_method() === $this->wlopPaymentGatewayId && $wcOrder->get_meta(OrderMetaKeys::MANUAL_CAPTURE_SENT) !== 'yes' && in_array((int) $wcOrder->get_meta(OrderMetaKeys::TRANSACTION_STATUS_CODE), [5, 56], \true);
    }
}
