<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Validator;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Products\GetPaymentProductsParams;
class CurrencySupportValidator
{
    private MerchantClientInterface $apiClient;
    public function __construct(MerchantClientInterface $apiClient)
    {
        $this->apiClient = $apiClient;
    }
    /**
     * @throws Exception
     */
    public function wlopSupportStoreCurrency() : bool
    {
        $currencyValidatorProperties = $this->currencyProperties();
        $isSupportedFromCache = \get_transient($currencyValidatorProperties->transientKey());
        if ($isSupportedFromCache) {
            return $isSupportedFromCache === 'yes';
        }
        return $this->updateWlopStoreCurrencySupport();
    }
    /**
     * @throws Exception
     */
    public function updateWlopStoreCurrencySupport() : bool
    {
        $currencyProperties = $this->currencyProperties();
        $isSupported = \true;
        try {
            $parameters = new GetPaymentProductsParams();
            $parameters->setCountryCode($currencyProperties->country());
            $parameters->setCurrencyCode($currencyProperties->currency());
            $parameters->setHide(['fields', 'accountsOnFile', 'translations']);
            $products = $this->apiClient->products()->getPaymentProducts($parameters);
            $paymentProducts = $products->getPaymentProducts();
            $isSupported = \is_array($paymentProducts) && \count($paymentProducts) > 0;
        } catch (\Throwable $exception) {
            \do_action('wlop.payment_products_error', ['exception' => $exception]);
        }
        \set_transient($currencyProperties->transientKey(), $isSupported ? 'yes' : 'no', 3600);
        return $isSupported;
    }
    protected function currencyProperties() : CurrencyValidatorProperties
    {
        $currency = \get_woocommerce_currency();
        $country = \WC()->countries->get_base_country();
        if (!\is_null(\WC()->customer)) {
            $country = \WC()->customer->get_billing_country();
        }
        return new CurrencyValidatorProperties($country, $currency, "wlop_store_currency_supported_{$country}_{$currency}");
    }
}
