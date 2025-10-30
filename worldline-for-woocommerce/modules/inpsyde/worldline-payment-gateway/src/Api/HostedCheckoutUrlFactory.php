<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProductFilter;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5403SpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
class HostedCheckoutUrlFactory
{
    private MerchantClientInterface $apiClient;
    private Transformer $requestTransformer;
    public function __construct(MerchantClientInterface $apiClient, Transformer $requestTransformer)
    {
        $this->apiClient = $apiClient;
        $this->requestTransformer = $requestTransformer;
    }
    /**
     * @throws Exception
     */
    public function create(HostedCheckoutInput $input) : CreateHostedCheckoutResponse
    {
        $request = $this->requestTransformer->create(CreateHostedCheckoutRequest::class, $input);
        $productFilterHostedCheckout = new PaymentProductFiltersHostedCheckout();
        $excludeMealVoucherFilter = new PaymentProductFilter();
        $excludeMealVoucherFilter->setProducts([5402]);
        // MEALVOCUHERS_PRODUCT_ID
        $productFilterHostedCheckout->setExclude($excludeMealVoucherFilter);
        $request->getHostedCheckoutSpecificInput()->setPaymentProductFilters($productFilterHostedCheckout);
        \assert($request instanceof CreateHostedCheckoutRequest);
        $redirectInput = $request->getRedirectPaymentMethodSpecificInput();
        if (!$redirectInput) {
            $redirectInput = new RedirectPaymentMethodSpecificInput();
        }
        $cvcoSpecificInput = new RedirectPaymentProduct5403SpecificInput();
        $cvcoSpecificInput->setCompleteRemainingPaymentAmount(\true);
        $redirectInput->setPaymentProduct5403SpecificInput($cvcoSpecificInput);
        $request->setRedirectPaymentMethodSpecificInput($redirectInput);
        $modifier = $input->hostedCheckoutRequestModifier();
        if (!\is_null($modifier)) {
            $request = $modifier->modify($request, $input);
        }
        $hostedCheckoutClient = $this->apiClient->hostedCheckout();
        return $hostedCheckoutClient->createHostedCheckout($request);
    }
}
