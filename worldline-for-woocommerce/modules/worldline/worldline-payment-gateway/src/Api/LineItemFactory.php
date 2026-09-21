<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Worldline\Vendor\Worldline\Transformer\Exception\TransformerException;
use Worldline\Vendor\Worldline\Transformer\Transformer;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\LineItem;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\OrderLineDetails;
class LineItemFactory
{
    /**
     * @throws TransformerException
     */
    public function create(\WC_Order_Item_Product $wcLineItem, Transformer $transformer) : LineItem
    {
        $wlopLineItem = new LineItem();
        $details = $this->lineItemDetails($wcLineItem, $transformer);
        // Derived from the per-unit figures we are about to send, not from the line total, so that
        // (productPrice + taxAmount) * quantity always equals this amount exactly. Whatever this
        // leaves against the shop's own line total is a rounding residue, settled once for the
        // whole order by CartTotalsReconciler.
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setCurrencyCode($wcLineItem->get_order()->get_currency());
        $amountOfMoney->setAmount(((int) $details->getProductPrice() + (int) $details->getTaxAmount()) * (int) $details->getQuantity());
        $wlopLineItem->setAmountOfMoney($amountOfMoney);
        $wlopLineItem->setOrderLineDetails($details);
        return $wlopLineItem;
    }
    /**
     * @throws TransformerException
     */
    protected function lineItemDetails(\WC_Order_Item_Product $wcLineItem, Transformer $transformer) : OrderLineDetails
    {
        $orderLineDetails = new OrderLineDetails();
        $order = $wcLineItem->get_order();
        $wcSubTotalPrice = $order->get_item_subtotal($wcLineItem);
        $productPrice = $transformer->create(AmountOfMoney::class, new WcPriceStruct((string) $wcSubTotalPrice, $wcLineItem->get_order()->get_currency()));
        \assert($productPrice instanceof AmountOfMoney);
        $orderLineDetails->setProductPrice($productPrice->getAmount());
        $lineItemUnitTax = $order->get_item_subtotal($wcLineItem, \true) - $wcSubTotalPrice;
        $taxAmount = $transformer->create(AmountOfMoney::class, new WcPriceStruct((string) $lineItemUnitTax, $order->get_currency()));
        \assert($taxAmount instanceof AmountOfMoney);
        $orderLineDetails->setTaxAmount($taxAmount->getAmount());
        $orderLineDetails->setQuantity($wcLineItem->get_quantity());
        $orderLineDetails->setProductName($wcLineItem->get_name());
        return $orderLineDetails;
    }
}
