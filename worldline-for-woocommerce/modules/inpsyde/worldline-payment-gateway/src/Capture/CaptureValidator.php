<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Capture;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use WC_Order;
class CaptureValidator
{
    /**
     * Checks whether the order uses a supported Worldline payment method.
     */
    public function isWlopPaymentMethod(WC_Order $wcOrder) : bool
    {
        return \in_array($wcOrder->get_payment_method(), GatewayIds::ALL, \true);
    }
    /**
     * Checks whether the order is still in a state where capture is allowed.
     *
     * @throws Exception
     */
    public function canCaptureAuthorization(WC_Order $wcOrder) : bool
    {
        $statusCode = (int) $wcOrder->get_meta(OrderMetaKeys::TRANSACTION_STATUS_CODE);
        return \in_array($statusCode, [5, 56], \true);
    }
    /**
     * Validates that the requested capture amount is within the allowed range.
     */
    public function validateAmountCents(int $requestedCents, int $availableCents) : void
    {
        if ($requestedCents <= 0) {
            throw new \Exception(\__('Capture amount must be greater than zero.', 'worldline-for-woocommerce'));
        }
        if ($requestedCents > $availableCents) {
            throw new \Exception(\__('The amount cannot exceed the available amount of the transaction.', 'worldline-for-woocommerce'));
        }
    }
    /**
     * Calculates the remaining amount available for capture.
     */
    public function availableCents(WC_Order $order) : int
    {
        $total = (int) \round((float) $order->get_total() * 100);
        $captured = (int) $order->get_meta(OrderMetaKeys::PAYMENT_CAPTURED_AMOUNT, \true);
        $cancelled = (int) $order->get_meta(OrderMetaKeys::PAYMENT_CANCELED_AMOUNT, \true);
        return \max(0, $total - $captured - $cancelled);
    }
}
