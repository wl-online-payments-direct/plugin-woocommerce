<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Worldline\Vendor\Worldline\Transformer\Exception\TransformerException;
use Worldline\Vendor\Worldline\Transformer\Transformer;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\CartTotalsReconciler;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\MismatchHandlerInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\PaymentMismatchValidator;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\Customer;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\Discount;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\LineItem;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\Order;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\OrderReferences;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\Shipping;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\ShoppingCart;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\SurchargeSpecificInput;
use WC_Order;
use WC_Order_Item_Product;
class WcOrderBasedOrderFactory implements WcOrderBasedOrderFactoryInterface
{
    private Transformer $transformer;
    private PaymentMismatchValidator $paymentMismatchValidator;
    private MismatchHandlerInterface $mismatchHandler;
    private CartTotalsReconciler $cartTotalsReconciler;
    private bool $surchargeEnabled;
    private bool $sendShoppingCart;
    public function __construct(Transformer $transformer, PaymentMismatchValidator $paymentMismatchValidator, MismatchHandlerInterface $mismatchHandler, CartTotalsReconciler $cartTotalsReconciler, bool $surchargeEnabled, bool $sendShoppingCart)
    {
        $this->transformer = $transformer;
        $this->paymentMismatchValidator = $paymentMismatchValidator;
        $this->mismatchHandler = $mismatchHandler;
        $this->cartTotalsReconciler = $cartTotalsReconciler;
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
            $amountOfMoneyDiscount = $this->transformer->create(AmountOfMoney::class, new WcPriceStruct((string) $discountWc, $wcOrder->get_currency()));
            $discount = new Discount();
            $discount->setAmount($amountOfMoneyDiscount->getAmount());
            $wlopOrder->setDiscount($discount);
        }
        $this->cartTotalsReconciler->reconcile($wlopOrder);
        try {
            $this->paymentMismatchValidator->validate($wlopOrder);
        } catch (\Throwable $exception) {
            $this->mismatchHandler->handle($wlopOrder, $exception);
        }
        return $wlopOrder;
    }
}
