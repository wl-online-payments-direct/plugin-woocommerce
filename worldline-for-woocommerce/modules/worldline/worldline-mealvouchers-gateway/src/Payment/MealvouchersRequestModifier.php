<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\MealvouchersGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\LineItem;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\Order;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\OrderLineDetails;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RedirectionData;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5402SpecificInput;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ShoppingCart;
use WC_Order_Item_Product;
class MealvouchersRequestModifier extends AbstractHostedPaymentRequestModifier
{
    public function modify(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : CreateHostedCheckoutRequest
    {
        $hostedSpecificInput = $hostedCheckoutRequest->getHostedCheckoutSpecificInput();
        if ($hostedSpecificInput) {
            $hostedSpecificInput->setPaymentProductFilters(null);
        }
        $hostedCheckoutRequest->setCardPaymentMethodSpecificInput(null);
        $hostedCheckoutRequest->setMobilePaymentMethodSpecificInput(null);
        $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
        $redirectPaymentMethodSpecificInput->setPaymentProductId(5402);
        $mealvoucherSpecificInput = new RedirectPaymentProduct5402SpecificInput();
        $mealvoucherSpecificInput->setCompleteRemainingPaymentAmount(\true);
        $redirectPaymentMethodSpecificInput->setPaymentProduct5402SpecificInput($mealvoucherSpecificInput);
        $redirectPaymentMethodSpecificInput->setRequiresApproval(\false);
        $redirectionData = new RedirectionData();
        $redirectionData->setReturnUrl($hostedCheckoutInput->returnUrl());
        $redirectPaymentMethodSpecificInput->setRedirectionData($redirectionData);
        $hostedCheckoutRequest->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);
        $customer = $hostedCheckoutRequest->getOrder()->getCustomer();
        $customer->setMerchantCustomerId((string) \get_current_user_id());
        $this->mergeCartItems($hostedCheckoutRequest, $hostedCheckoutInput);
        $this->removeTokensFromRequest($hostedCheckoutRequest);
        return $hostedCheckoutRequest;
    }
    /**
     * Merge all cart items into a single item as required for Mealvoucher transactions.
     *
     * @param CreateHostedCheckoutRequest $hostedCheckoutRequest
     * @param HostedCheckoutInput $hostedCheckoutInput
     *
     * @return void
     */
    private function mergeCartItems(CreateHostedCheckoutRequest $hostedCheckoutRequest, HostedCheckoutInput $hostedCheckoutInput) : void
    {
        $order = $hostedCheckoutRequest->getOrder();
        if (!$order || !$order->getShoppingCart()) {
            return;
        }
        $order->discount = null;
        $wcOrder = $hostedCheckoutInput->wcOrder();
        global $wpdb;
        $table = $wpdb->prefix . 'product_type';
        $totalAmount = 0;
        // total amount including tax
        $totalPrice = 0;
        // total price excluding tax
        $totalTax = 0;
        // total tax across all items
        $totalDiscount = 0;
        // total discount sum
        $names = [];
        // product names for concatenation
        $productTypes = [];
        // all product types
        $units = [];
        // units used across items
        foreach ($wcOrder->get_items() as $order_item) {
            if (!$order_item instanceof WC_Order_Item_Product) {
                continue;
            }
            $product_id = $order_item->get_product_id();
            $product = $order_item->get_product();
            if (!$product) {
                continue;
            }
            // 1. Determine product type
            $type = $wpdb->get_var($wpdb->prepare("SELECT `type` FROM `{$table}` WHERE product_id = %d", $product_id));
            if ($type) {
                $productTypes[] = $this->mapProductType($type);
                // Map to Worldline values
            }
            // 2. Calculate line totals
            $line_total = (int) \round($order_item->get_total() * 100);
            // price without tax
            $line_tax = (int) \round($order_item->get_total_tax() * 100);
            // tax amount
            $line_subtotal = (int) \round($order_item->get_subtotal() * 100);
            // original price before discount
            $discount = \max(0, $line_subtotal - $line_total);
            $totalPrice += $line_total;
            $totalAmount += $line_total + $line_tax;
            $totalTax += $line_tax;
            $totalDiscount += $discount;
            // 3. Add product name
            for ($i = 0; $i < $order_item->get_quantity(); $i++) {
                $names[] = $order_item->get_name();
            }
            // 4. Collect unit attributes if available
            $unit = $product->get_attribute('unit');
            if ($unit) {
                $units[] = $unit;
            }
        }
        // 5. Determine merged product type based on rules
        $mergedType = $this->resolveProductType($productTypes);
        // 6. Determine merged product name
        $mergedName = $this->resolveProductName($names, $mergedType);
        // 7. Determine unit
        $unit = \count(\array_unique($units)) === 1 ? \reset($units) : 'Merged item';
        /*
         * 8. Create AmountOfMoney for the merged item.
         *
         * Derived from the order total, not from the per-line figures summed above: those are
         * each rounded on their own, while WooCommerce rounds the order's tax once over the
         * whole order, so their sum can land a cent away from the total. The total is what the
         * shopper is charged and what Worldline validates the parts against - it rejects the
         * whole request with error 1099 when they do not add up - so the residue is absorbed
         * into this one line.
         */
        $mergedAmountValue = $this->amountFromOrderTotal($order, $totalAmount);
        $mergedAmount = new AmountOfMoney();
        $mergedAmount->setAmount($mergedAmountValue);
        $mergedAmount->setCurrencyCode($order->getAmountOfMoney()->getCurrencyCode());
        // 9. Create OrderLineDetails
        $orderLineDetails = new OrderLineDetails();
        $orderLineDetails->setProductCode('Merged item');
        // always "Merged item"
        $orderLineDetails->setProductName($mergedName);
        // concatenated or shortened
        $orderLineDetails->setProductPrice($mergedAmountValue - $totalTax + $totalDiscount);
        $orderLineDetails->setProductType($mergedType);
        // resolved type
        $orderLineDetails->setQuantity(1);
        // always 1
        $orderLineDetails->setTaxAmount($totalTax);
        // total tax
        $orderLineDetails->setUnit($unit);
        // unit or "Merged item"
        if ($totalDiscount > 0) {
            $orderLineDetails->setDiscountAmount($totalDiscount);
        }
        // 10. Create final single LineItem
        $mergedItem = new LineItem();
        $mergedItem->setAmountOfMoney($mergedAmount);
        $mergedItem->setOrderLineDetails($orderLineDetails);
        // 11. Replace entire shopping cart with one merged item
        $shoppingCart = new ShoppingCart();
        $shoppingCart->setItems([$mergedItem]);
        $order->setShoppingCart($shoppingCart);
    }
    /**
     * Maps product types from DB to Worldline values.
     */
    private function mapProductType(string $type) : string
    {
        switch (\strtolower($type)) {
            case 'food':
                return 'FoodAndDrink';
            case 'home':
                return 'HomeAndGarden';
            case 'gift':
            default:
                return 'None';
        }
    }
    /**
     * Apply product type priority: FoodAndDrink > HomeAndGarden > GiftAndFlowers.
     */
    private function resolveProductType(array $types) : string
    {
        if (\in_array('FoodAndDrink', $types, \true)) {
            return 'FoodAndDrink';
        }
        if (\in_array('HomeAndGarden', $types, \true)) {
            return 'HomeAndGarden';
        }
        return 'GiftAndFlowers';
    }
    /**
     * Concatenates product names and trims if needed.
     */
    private function resolveProductName(array $names, string $type) : string
    {
        $name = \implode(' + ', $names);
        if (\strlen($name) > 50) {
            $count = \count($names);
            return "{$count} {$type} Items";
        }
        return $name;
    }
    /**
     * What the merged line has to come to for the order to add up: the order total, less the
     * shipping the same request carries. The discount is cleared before this runs, so nothing
     * else is left to subtract.
     *
     * Falls back to the summed figure when there is no total to derive from.
     */
    private function amountFromOrderTotal(Order $order, int $summedAmount) : int
    {
        $amountOfMoney = $order->getAmountOfMoney();
        if (!$amountOfMoney) {
            return $summedAmount;
        }
        $amount = (int) $amountOfMoney->getAmount();
        $shipping = $order->getShipping();
        if ($shipping) {
            $amount -= (int) $shipping->getShippingCost() + (int) $shipping->getShippingCostTax();
        }
        return $amount;
    }
}
