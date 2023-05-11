<?php declare(strict_types=1);

namespace MoptWorldline\Service;

use MoptWorldline\Adapter\WorldlineSDKAdapter;
use OnlinePayments\Sdk\Domain\LineItem;

class DiscountHelper
{
    /**
     * @param array $lineItems
     * @param int $discount
     * @param array $maxPrices
     * @return array
     */
    public static function handleDiscount(array $lineItems, int $discount, array $maxPrices): array
    {
        // Add discount to most expensive unit
        if ($maxPrices['unit']['price'] > $discount) {
            $id = $maxPrices['unit']['id'];
            $item = $lineItems[$id]->toObject();
            if ($item->orderLineDetails->quantity > 1) {
                self::generateDiscountedItem($lineItems, $item, 1, $discount, $id);
            } else {
                self::addDiscountToItem($lineItems, $item, $discount, $id);
            }
            return $lineItems;
        }

        // Split discount in item (same type units)
        if ($maxPrices['item']['price'] > $discount) {
            self::splitDiscountInItem($lineItems, $maxPrices['item']['id'], $discount);
            return $lineItems;
        }

        // Split discount by items
        /** @var LineItem $lineItem */
        foreach ($lineItems as $key => $lineItem) {
            $item = $lineItem->toObject();
            $partDiscount = $item->amountOfMoney->amount - $item->orderLineDetails->quantity;
            if ($discount >= $partDiscount) {
                if ($item->orderLineDetails->quantity > 1) {
                    self::splitDiscountInItem($lineItems, $key, $partDiscount);
                } else {
                    self::addDiscountToItem($lineItems, $item, $partDiscount, $key);
                }
                $discount -= $partDiscount;
            } else {
                self::splitDiscountInItem($lineItems, $key, $discount);
                $discount = 0;
            }
        }

        return $lineItems;
    }

    /**
     * @param array $lineItems
     * @param object $item
     * @param int $discount
     * @param string $id
     * @return void
     */
    private static function addDiscountToItem(array &$lineItems,object $item, int $discount, string $id): void
    {
        $item->orderLineDetails->discountAmount = $discount;
        $item->amountOfMoney->amount -= $discount;
        self::replaceLineItem($lineItems, $item, $id);
    }

    /**
     * @param array $lineItems
     * @param object $item
     * @param int $quantity
     * @param int $discount
     * @param string $id
     * @return void
     */
    private static function generateDiscountedItem(array &$lineItems, object $item, int $quantity, int $discount, string $id): void
    {
        $discountedItem = WorldlineSDKAdapter::createLineItem
        (
            $item->orderLineDetails->productName,
            $item->amountOfMoney->currencyCode,
            ($item->orderLineDetails->productPrice - $discount) * $quantity,
            $item->orderLineDetails->productPrice,
            $quantity,
            $discount
        );
        $lineItems[] = $discountedItem;

        $item->orderLineDetails->quantity -= $quantity;
        $item->amountOfMoney->amount -= $item->orderLineDetails->productPrice * $quantity;
        self::replaceLineItem($lineItems, $item, $id);
    }

    /**
     * @param array $lineItems
     * @param string $itemId
     * @param int $discount
     * @return void
     */
    private static function splitDiscountInItem(array &$lineItems, string $itemId, int $discount): void
    {
        $item = $lineItems[$itemId]->toObject();
        $quantity = $item->orderLineDetails->quantity;
        $leftover = $discount % $quantity;
        if ($leftover) {
            $leftoverPart = $discount % ($quantity - 1);
            $partDiscount = (int)($discount / ($quantity - 1));

            self::generateDiscountedItem($lineItems, $item, $quantity - 1, $partDiscount, $itemId);
            if ($leftoverPart) {
                $newItem = $lineItems[$itemId]->toObject();
                self::addDiscountToItem($lineItems, $newItem, $leftoverPart, $itemId);
            }
        } else {
            $item->orderLineDetails->discountAmount = $discount / $quantity;
            $item->amountOfMoney->amount -= $discount;
            self::replaceLineItem($lineItems, $item, $itemId);
        }
    }

    /**
     * @param array $lineItems
     * @param object $replacer
     * @param string $id
     * @return void
     */
    private static function replaceLineItem(array &$lineItems, object $replacer, string $id): void
    {
        $item = new LineItem();
        $lineItems[$id] = $item->fromObject($replacer);
    }
}
