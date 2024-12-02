<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Inpsyde\Transformer\ConfigurableTransformer;
use Syde\Vendor\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Dhii\Services\Factory;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AmountOfMoneyFactory;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\LineItemFactory;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Syde\Vendor\OnlinePayments\Sdk\Domain\Address;
use Syde\Vendor\OnlinePayments\Sdk\Domain\AddressPersonal;
use Syde\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\OnlinePayments\Sdk\Domain\ContactDetails;
use Syde\Vendor\OnlinePayments\Sdk\Domain\Customer;
use Syde\Vendor\OnlinePayments\Sdk\Domain\LineItem;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PersonalInformation;
use Syde\Vendor\OnlinePayments\Sdk\Domain\PersonalName;
use Syde\Vendor\OnlinePayments\Sdk\Domain\Shipping;
return new Factory(['worldline_payment_gateway.amount_of_money_factory'], static function (AmountOfMoneyFactory $amountOfMoneyFactory): Transformer {
    $transformer = new ConfigurableTransformer();
    $transformer->addTransformer(static function (WcPriceStruct $priceStruct) use ($amountOfMoneyFactory): AmountOfMoney {
        return $amountOfMoneyFactory->create($priceStruct);
    });
    $transformer->addTransformer(static function (\WC_Order_Item_Product $wcLineItem, Transformer $transformer): LineItem {
        $lineItemFactory = new LineItemFactory();
        return $lineItemFactory->create($wcLineItem, $transformer);
    });
    $transformer->addTransformer(static function (\WC_Order $wcOrder, Transformer $transformer): Customer {
        $customer = new Customer();
        $personalInfo = new PersonalInformation();
        if ($wcOrder->get_billing_first_name()) {
            $name = new PersonalName();
            $name->setFirstName($wcOrder->get_billing_first_name());
            $name->setSurname($wcOrder->get_billing_last_name());
            $personalInfo->setName($name);
        }
        $customer->setPersonalInformation($personalInfo);
        $contact = new ContactDetails();
        if ($wcOrder->get_billing_email()) {
            $contact->setEmailAddress($wcOrder->get_billing_email());
        }
        $phone = $wcOrder->get_billing_phone();
        // Looks like there is no separate shipping phone in WL, so sending WC shipping phone if both set.
        if ($wcOrder->has_shipping_address() && $wcOrder->get_shipping_phone()) {
            $phone = $wcOrder->get_shipping_phone();
        }
        if ($phone) {
            $contact->setPhoneNumber($phone);
        }
        $customer->setContactDetails($contact);
        $address = new Address();
        if ($wcOrder->get_billing_country()) {
            $address->setCountryCode($wcOrder->get_billing_country());
        }
        if ($wcOrder->get_billing_state()) {
            $address->setState($wcOrder->get_billing_state());
        }
        if ($wcOrder->get_billing_city()) {
            $address->setCity($wcOrder->get_billing_city());
        }
        if ($wcOrder->get_billing_postcode()) {
            $address->setZip($wcOrder->get_billing_postcode());
        }
        if ($wcOrder->get_billing_address_1()) {
            $address->setStreet($wcOrder->get_billing_address_1());
        }
        if ($wcOrder->get_billing_address_2()) {
            $address->setAdditionalInfo($wcOrder->get_billing_address_2());
        }
        $customer->setBillingAddress($address);
        return $customer;
    });
    $transformer->addTransformer(static function (\WC_Order $wcOrder, Transformer $transformer) use ($amountOfMoneyFactory): Shipping {
        $shipping = new Shipping();
        if ($wcOrder->get_billing_email()) {
            // only one email in WC, no separate shipping email
            $shipping->setEmailAddress($wcOrder->get_billing_email());
        }
        $address = new AddressPersonal();
        if ($wcOrder->get_shipping_first_name()) {
            $name = new PersonalName();
            $name->setFirstName($wcOrder->get_shipping_first_name());
            $name->setSurname($wcOrder->get_shipping_last_name());
            $address->setName($name);
        }
        if ($wcOrder->get_shipping_country()) {
            $address->setCountryCode($wcOrder->get_shipping_country());
        }
        if ($wcOrder->get_shipping_state()) {
            $address->setState($wcOrder->get_shipping_state());
        }
        if ($wcOrder->get_shipping_city()) {
            $address->setCity($wcOrder->get_shipping_city());
        }
        if ($wcOrder->get_shipping_postcode()) {
            $address->setZip($wcOrder->get_shipping_postcode());
        }
        if ($wcOrder->get_shipping_address_1()) {
            $address->setStreet($wcOrder->get_shipping_address_1());
        }
        if ($wcOrder->get_shipping_address_2()) {
            $address->setAdditionalInfo($wcOrder->get_shipping_address_2());
        }
        $shipping->setAddress($address);
        $cost = $amountOfMoneyFactory->create(new WcPriceStruct($wcOrder->get_shipping_total(), $wcOrder->get_currency()));
        $tax = $amountOfMoneyFactory->create(new WcPriceStruct($wcOrder->get_shipping_tax(), $wcOrder->get_currency()));
        $shipping->setShippingCost($cost->getAmount());
        $shipping->setShippingCostTax($tax->getAmount());
        return $shipping;
    });
    return $transformer;
});
