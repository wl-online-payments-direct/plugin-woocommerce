<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Inpsyde\Transformer\ConfigurableTransformer;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AmountOfMoneyFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\LineItemFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Customer\AccountTypeHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Shipping\AddressIndicatorHandler;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Address;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AddressPersonal;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\BrowserData;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ContactDetails;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Customer;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CustomerDevice;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\LineItem;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PersonalInformation;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PersonalName;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Shipping;
return new Factory(['worldline_payment_gateway.amount_of_money_factory', 'worldline_payment_gateway.account_type_handler', 'worldline_payment_gateway.address_indicator_handler', 'worldline_payment_gateway.locale', 'utils.client_ip_address', 'utils.client_user_agent', 'utils.client_accept', 'worldline_payment_gateway.customer_screen_height', 'worldline_payment_gateway.customer_screen_width', 'worldline_payment_gateway.customer_color_depth', 'worldline_payment_gateway.customer_java_enabled', 'worldline_payment_gateway.customer_timezone_offset'], static function (AmountOfMoneyFactory $amountOfMoneyFactory, AccountTypeHandler $accountTypeHandler, AddressIndicatorHandler $addressIndicatorHandler, string $locale, ?string $ipAddress, ?string $userAgent, ?string $acceptHeader, ?int $screenHeight, ?int $screenWidth, ?int $colorDepth, ?bool $javaEnabled, ?int $timezoneOffsetUtcMinutes) : Transformer {
    $transformer = new ConfigurableTransformer();
    $transformer->addTransformer(static function (WcPriceStruct $priceStruct) use($amountOfMoneyFactory) : AmountOfMoney {
        return $amountOfMoneyFactory->create($priceStruct);
    });
    $transformer->addTransformer(static function (\WC_Order_Item_Product $wcLineItem, Transformer $transformer) : LineItem {
        $lineItemFactory = new LineItemFactory();
        return $lineItemFactory->create($wcLineItem, $transformer);
    });
    $transformer->addTransformer(static function (\WC_Order $wcOrder, Transformer $transformer) use($ipAddress, $userAgent, $acceptHeader, $accountTypeHandler, $screenHeight, $screenWidth, $locale, $colorDepth, $javaEnabled, $timezoneOffsetUtcMinutes) : Customer {
        $customer = new Customer();
        $accountType = $accountTypeHandler->determineAccountType($wcOrder);
        $personalInfo = new PersonalInformation();
        if ($wcOrder->get_billing_first_name()) {
            $name = new PersonalName();
            $name->setFirstName($wcOrder->get_billing_first_name());
            $name->setSurname($wcOrder->get_billing_last_name());
            $personalInfo->setName($name);
        }
        $customer->setPersonalInformation($personalInfo);
        $customer->setLocale($locale);
        $customer->setAccountType($accountType);
        $customerDevice = new CustomerDevice();
        $customerDevice->setLocale($locale);
        if (!\is_null($acceptHeader)) {
            $customerDevice->setAcceptHeader($acceptHeader);
        }
        if (!\is_null($ipAddress)) {
            $customerDevice->setIpAddress($ipAddress);
        }
        if (!\is_null($userAgent)) {
            $customerDevice->setUserAgent($userAgent);
        }
        if ($screenHeight !== null && $screenWidth !== null || $colorDepth !== null || $javaEnabled !== null) {
            $browserData = new BrowserData();
            $browserData->setScreenHeight($screenHeight);
            $browserData->setScreenWidth($screenWidth);
            $browserData->setColorDepth($colorDepth);
            $browserData->setJavaEnabled($javaEnabled);
            $customerDevice->setBrowserData($browserData);
        }
        if ($timezoneOffsetUtcMinutes !== null) {
            $customerDevice->setTimezoneOffsetUtcMinutes($timezoneOffsetUtcMinutes);
        }
        $customer->setDevice($customerDevice);
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
    $transformer->addTransformer(static function (\WC_Order $wcOrder, Transformer $transformer) use($amountOfMoneyFactory, $addressIndicatorHandler) : Shipping {
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
        $shipping->setAddressIndicator($addressIndicatorHandler->determineAddressType($wcOrder));
        return $shipping;
    });
    return $transformer;
});
