<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Checkout;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage\ReturnPageStatus;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage\WcOrderStatusChecker;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class ReturnPageStatusChecker extends WcOrderStatusChecker
{
    public function determineStatus(?WC_Order $wcOrder) : string
    {
        if (!$wcOrder) {
            throw new Exception('WC order required.');
        }
        $wlopWcOrder = new WlopWcOrder($wcOrder);
        if ($wlopWcOrder->statusCode() === 1) {
            return ReturnPageStatus::CANCELLED;
        }
        if (\in_array($wcOrder->get_status(), ['pending', 'on-hold'], \true) && $this->isAbandonedPartialVoucher($wcOrder)) {
            if ($wcOrder->get_status() !== 'failed') {
                $wcOrder->update_status('failed', \__('Payment cancelled by customer on Worldline hosted page.', 'worldline-for-woocommerce'));
            }
            return ReturnPageStatus::CANCELLED;
        }
        return parent::determineStatus($wcOrder);
    }
    private function isAbandonedPartialVoucher(WC_Order $wcOrder) : bool
    {
        $voucherProductIds = [3112, 5402, 5403];
        $productId = (int) $wcOrder->get_meta(OrderMetaKeys::PAYMENT_METHOD_PRODUCT_ID);
        if (!\in_array($productId, $voucherProductIds, \true)) {
            return \false;
        }
        $acquiredRaw = (string) $wcOrder->get_meta(OrderMetaKeys::PAYMENT_TOTAL_AMOUNT);
        if ($acquiredRaw === '' || !\is_numeric($acquiredRaw)) {
            return \false;
        }
        $acquired = (int) $acquiredRaw;
        if ($acquired <= 0) {
            return \false;
        }
        $orderTotalCents = (int) \round($wcOrder->get_total() * 100);
        return $acquired < $orderTotalCents;
    }
}
