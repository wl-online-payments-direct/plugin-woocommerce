<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\Discount;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\LineItem;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\Order;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\OrderLineDetails;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ShoppingCart;
/**
 * Makes the parts of an order add up to its total before the request leaves the plugin.
 *
 * Every money value is converted to minor units on its own, so the sum of the parts can land a
 * fraction of a minor unit away from the shop's own total - the shop rounds the total once, we
 * round each component. The residue is not an error and the total is authoritative, so it is
 * absorbed here rather than reported: a short sum gets a "Rounding" line item, a long sum is
 * folded into the discount.
 *
 * A difference larger than one major currency unit is left untouched, because at that size it is
 * no longer a rounding residue - PaymentMismatchValidator still gets to see it.
 */
class CartTotalsReconciler
{
    public const ROUNDING_PRODUCT_CODE = 'rounding';
    public const ROUNDING_PRODUCT_NAME = 'Rounding';
    private MoneyAmountConverter $moneyAmountConverter;
    public function __construct(MoneyAmountConverter $moneyAmountConverter)
    {
        $this->moneyAmountConverter = $moneyAmountConverter;
    }
    public function reconcile(Order $wlopOrder) : void
    {
        $cart = $wlopOrder->getShoppingCart();
        if ($cart === null) {
            return;
        }
        $items = $cart->getItems();
        if ($items === null || $items === []) {
            return;
        }
        $amountOfMoney = $wlopOrder->getAmountOfMoney();
        if ($amountOfMoney === null) {
            return;
        }
        $difference = (int) $amountOfMoney->getAmount() - $this->partsTotal($wlopOrder, $items);
        if ($difference === 0) {
            return;
        }
        $currency = (string) $amountOfMoney->getCurrencyCode();
        if (\abs($difference) > $this->moneyAmountConverter->centDecimalConversionFactor($currency)) {
            return;
        }
        if ($difference > 0) {
            $this->addRoundingLineItem($cart, $items, $difference, $currency);
            return;
        }
        $this->increaseDiscount($wlopOrder, -$difference);
    }
    /**
     * What the shop total would have to be for the parts we are about to send to be consistent.
     *
     * @param array<LineItem> $items
     */
    private function partsTotal(Order $wlopOrder, array $items) : int
    {
        $total = 0;
        foreach ($items as $lineItem) {
            $itemAmount = $lineItem->getAmountOfMoney();
            if ($itemAmount) {
                $total += (int) $itemAmount->getAmount();
            }
        }
        $shipping = $wlopOrder->getShipping();
        if ($shipping) {
            $total += (int) $shipping->getShippingCost() + (int) $shipping->getShippingCostTax();
        }
        $discount = $wlopOrder->getDiscount();
        if ($discount) {
            $total -= (int) $discount->getAmount();
        }
        return $total;
    }
    /**
     * @param array<LineItem> $items
     */
    private function addRoundingLineItem(ShoppingCart $cart, array $items, int $amount, string $currency) : void
    {
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($amount);
        $amountOfMoney->setCurrencyCode($currency);
        $orderLineDetails = new OrderLineDetails();
        $orderLineDetails->setProductPrice($amount);
        $orderLineDetails->setTaxAmount(0);
        $orderLineDetails->setQuantity(1);
        $orderLineDetails->setProductName(self::ROUNDING_PRODUCT_NAME);
        $orderLineDetails->setProductCode(self::ROUNDING_PRODUCT_CODE);
        $lineItem = new LineItem();
        $lineItem->setAmountOfMoney($amountOfMoney);
        $lineItem->setOrderLineDetails($orderLineDetails);
        $items[] = $lineItem;
        $cart->setItems($items);
    }
    private function increaseDiscount(Order $wlopOrder, int $amount) : void
    {
        $discount = $wlopOrder->getDiscount();
        if (!$discount) {
            $discount = new Discount();
            $discount->setAmount($amount);
            $wlopOrder->setDiscount($discount);
            return;
        }
        $discount->setAmount((int) $discount->getAmount() + $amount);
    }
}
