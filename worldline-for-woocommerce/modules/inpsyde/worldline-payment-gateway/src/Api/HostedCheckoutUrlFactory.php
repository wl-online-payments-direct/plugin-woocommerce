<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Exception;
use Syde\Vendor\Inpsyde\Transformer\Transformer;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Syde\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
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
    public function create(HostedCheckoutInput $input): CreateHostedCheckoutResponse
    {
        $request = $this->requestTransformer->create(CreateHostedCheckoutRequest::class, $input);
        assert($request instanceof CreateHostedCheckoutRequest);
        $hostedCheckoutClient = $this->apiClient->hostedCheckout();
        return $hostedCheckoutClient->createHostedCheckout($request);
    }
}
