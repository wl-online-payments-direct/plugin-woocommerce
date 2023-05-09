<?php declare(strict_types=1);

namespace MoptWorldline\Service;

use MoptWorldline\Adapter\WorldlineSDKAdapter;
use OnlinePayments\Sdk\Domain\LineItem;

class DiscountHelper
{
    /**
     * @param array $lineItems
     * @param int $discount
     * @param ?string $maxPriceItemId
     * @param int $maxPrice
     * @return array
     */
    public static function handleDiscount(array $lineItems, int $discount, ?string $maxPriceItemId = null, int $maxPrice = 0): array
    {
        if ($maxPrice > $discount) {
            $item = $lineItems[$maxPriceItemId]->toObject();
            if ($item->orderLineDetails->quantity > 1) {
                self::generateDiscountedItem($lineItems, $item, 1, $discount, $maxPriceItemId);
            } else {
                $item->orderLineDetails->discountAmount = $discount;
                $item->amountOfMoney->amount -= $discount;
                self::replaceLineItem($lineItems, $item, $maxPriceItemId);
            }
            return $lineItems;
        }

        /** @var LineItem $lineItem */
        foreach ($lineItems as $key => $lineItem) {
            $item = $lineItem->toObject();
            if ($discount < $item->amountOfMoney->amount) {
                $quantity = $item->orderLineDetails->quantity;
                $leftover = $discount % $quantity;
                if ($leftover) {
                    $leftoverPart = $discount % ($quantity - 1);
                    $partDiscount = (int) ($discount / ($quantity - 1));

                    self::generateDiscountedItem($lineItems, $item, $quantity - 1, $partDiscount, $key);
                    if ($leftoverPart) {
                        $newItem = $lineItems[$key]->toObject();
                        $newItem->orderLineDetails->discountAmount = $leftoverPart;
                        $newItem->amountOfMoney->amount -= $leftoverPart;
                        self::replaceLineItem($lineItems, $newItem, $key);
                    }
                } else {
                    $item->orderLineDetails->discountAmount = $discount / $quantity;
                    $item->amountOfMoney->amount -= $discount;
                    self::replaceLineItem($lineItems, $item, $key);
                }
            }
        }

        return $lineItems;
    }

    /**
     * @param array $lineItems
     * @param object $item
     * @param int $quantity
     * @param int $discount
     * @param string $id
     * @return void
     */
    public static function generateDiscountedItem(array &$lineItems,object $item, int $quantity, int $discount, string $id): void
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
     * @param object $replacer
     * @param string $id
     * @return void
     */
    public static function replaceLineItem(array &$lineItems, object $replacer, string $id): void
    {
        $item = new LineItem();
        $lineItems[$id] = $item->fromObject($replacer);
    }
}
