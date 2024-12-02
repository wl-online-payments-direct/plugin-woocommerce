<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Syde\Vendor\Inpsyde\Transformer\Exception\TransformerException;
use Syde\Vendor\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\MismatchHandlerInterface;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\PaymentMismatchValidator;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Syde\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\OnlinePayments\Sdk\Domain\Customer;
use Syde\Vendor\OnlinePayments\Sdk\Domain\LineItem;
use Syde\Vendor\OnlinePayments\Sdk\Domain\Order;
use Syde\Vendor\OnlinePayments\Sdk\Domain\OrderReferences;
use Syde\Vendor\OnlinePayments\Sdk\Domain\Shipping;
use Syde\Vendor\OnlinePayments\Sdk\Domain\ShoppingCart;
use Syde\Vendor\OnlinePayments\Sdk\Domain\SurchargeSpecificInput;
use WC_Order;
use WC_Order_Item_Product;
class WcOrderBasedOrderFactory implements WcOrderBasedOrderFactoryInterface
{
    private Transformer $transformer;
    private PaymentMismatchValidator $paymentMismatchValidator;
    private MismatchHandlerInterface $mismatchHandler;
    private bool $surchargeEnabled;
    public function __construct(Transformer $transformer, PaymentMismatchValidator $paymentMismatchValidator, MismatchHandlerInterface $mismatchHandler, bool $surchargeEnabled)
    {
        $this->transformer = $transformer;
        $this->paymentMismatchValidator = $paymentMismatchValidator;
        $this->mismatchHandler = $mismatchHandler;
        $this->surchargeEnabled = $surchargeEnabled;
    }
    /**
     * @throws TransformerException
     */
    public function create(WC_Order $wcOrder): Order
    {
        $amountOfMoney = $this->transformer->create(AmountOfMoney::class, new WcPriceStruct((string) $wcOrder->get_total(), $wcOrder->get_currency()));
        $lineItems = array_map(function (WC_Order_Item_Product $lineItem): LineItem {
            return $this->transformer->create(LineItem::class, $lineItem);
        }, $wcOrder->get_items());
        $shoppingCart = new ShoppingCart();
        $shoppingCart->setItems($lineItems);
        $wlopOrder = new Order();
        $wlopOrder->setAmountOfMoney($amountOfMoney);
        $wlopOrder->setShoppingCart($shoppingCart);
        $ref = new OrderReferences();
        $ref->setMerchantReference((string) $wcOrder->get_id());
        $wlopOrder->setReferences($ref);
        $wlopOrder->setCustomer($this->transformer->create(Customer::class, $wcOrder));
        if ($wcOrder->has_shipping_address()) {
            $wlopOrder->setShipping($this->transformer->create(Shipping::class, $wcOrder));
        }
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
