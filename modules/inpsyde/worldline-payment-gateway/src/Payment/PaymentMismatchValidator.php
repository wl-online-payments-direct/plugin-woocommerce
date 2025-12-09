<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
// phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\LineItem;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Order;
class PaymentMismatchValidator
{
    /**
     * @throws Exception
     */
    public function validate(Order $wlopOrder) : void
    {
        $this->validateTotals($wlopOrder);
    }
    /**
     * @throws Exception
     */
    protected function validateTotals(Order $wlopOrder) : void
    {
        $orderCart = $wlopOrder->getShoppingCart();
        if (!$orderCart) {
            return;
        }
        $lineItemsTotal = 0;
        foreach ($orderCart->getItems() as $lineItem) {
            $itemTotal = $lineItem->getAmountOfMoney()->getAmount();
            $lineItemsTotal += $itemTotal;
            $details = $lineItem->getOrderLineDetails();
            if (!$details) {
                continue;
            }
            $detailsTotal = $details->getProductPrice();
            if ($details->getTaxAmount()) {
                $detailsTotal += $details->getTaxAmount();
            }
            if ($details->getDiscountAmount()) {
                $detailsTotal -= $details->getDiscountAmount();
            }
            $detailsTotal *= $details->getQuantity();
            if ($detailsTotal !== $itemTotal) {
                throw new Exception("Line item total price mismatch. WLOP item details total price in cents: {$detailsTotal}, WLOP item total price in cents: {$itemTotal}.");
            }
        }
        $orderTotal = $wlopOrder->getAmountOfMoney()->getAmount();
        $testTotal = 0;
        $shipping = $wlopOrder->getShipping();
        if ($shipping) {
            $testTotal += $shipping->getShippingCost() + $shipping->getShippingCostTax();
        }
        $discount = $wlopOrder->getDiscount();
        if ($discount) {
            $testTotal -= $discount->getAmount();
        }
        if ($lineItemsTotal) {
            $testTotal += $lineItemsTotal;
        }
        if ($orderTotal !== $testTotal) {
            throw new Exception("Total price mismatch. WLOP TEST price in cents: {$testTotal}, WLOP total price in cents: {$orderTotal}.");
        }
    }
}
