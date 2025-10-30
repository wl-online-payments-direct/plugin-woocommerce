<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Syde\Vendor\Worldline\Inpsyde\Transformer\Exception\TransformerException;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\MismatchHandlerInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\PaymentMismatchValidator;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Customer;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\LineItem;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Order;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\OrderReferences;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Shipping;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ShoppingCart;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SurchargeSpecificInput;
use WC_Order;
use WC_Order_Item_Product;
class WcOrderBasedOrderFactory implements WcOrderBasedOrderFactoryInterface
{
    private Transformer $transformer;
    private PaymentMismatchValidator $paymentMismatchValidator;
    private MismatchHandlerInterface $mismatchHandler;
    private bool $surchargeEnabled;
    private bool $sendShoppingCart;
    public function __construct(Transformer $transformer, PaymentMismatchValidator $paymentMismatchValidator, MismatchHandlerInterface $mismatchHandler, bool $surchargeEnabled, bool $sendShoppingCart)
    {
        $this->transformer = $transformer;
        $this->paymentMismatchValidator = $paymentMismatchValidator;
        $this->mismatchHandler = $mismatchHandler;
        $this->surchargeEnabled = $surchargeEnabled;
        $this->sendShoppingCart = $sendShoppingCart;
    }
    /**
     * @throws TransformerException
     */
    public function create(WC_Order $wcOrder) : Order
    {
        $amountOfMoney = $this->transformer->create(AmountOfMoney::class, new WcPriceStruct((string) $wcOrder->get_total(), $wcOrder->get_currency()));
        $wlopOrder = new Order();
        $wlopOrder->setAmountOfMoney($amountOfMoney);
        if ($this->sendShoppingCart) {
            $lineItems = \array_map(function (WC_Order_Item_Product $lineItem) : LineItem {
                return $this->transformer->create(LineItem::class, $lineItem);
            }, $wcOrder->get_items());
            $shoppingCart = new ShoppingCart();
            $shoppingCart->setItems($lineItems);
            $wlopOrder->setShoppingCart($shoppingCart);
        }
        $ref = new OrderReferences();
        $ref->setMerchantReference((string) $wcOrder->get_id());
        $wlopOrder->setReferences($ref);
        $wlopOrder->setCustomer($this->transformer->create(Customer::class, $wcOrder));
        $wlopOrder->setShipping($this->transformer->create(Shipping::class, $wcOrder));
        if ($this->surchargeEnabled) {
            $surchargeSpecificInput = new SurchargeSpecificInput();
            $surchargeSpecificInput->setMode('on-behalf-of');
            $wlopOrder->setSurchargeSpecificInput($surchargeSpecificInput);
        }
        $discountWc = (float) $wcOrder->get_discount_total() + (float) $wcOrder->get_discount_tax();
        if ($discountWc !== 0.0) {
            $wlopOrder->setDiscount($this->transformer->create(AmountOfMoney::class, new WcPriceStruct((string) $discountWc, $wcOrder->get_currency())));
        }
        try {
            $this->paymentMismatchValidator->validate($wlopOrder);
        } catch (\Throwable $exception) {
            $this->mismatchHandler->handle($wlopOrder, $exception);
        }
        return $wlopOrder;
    }
}
