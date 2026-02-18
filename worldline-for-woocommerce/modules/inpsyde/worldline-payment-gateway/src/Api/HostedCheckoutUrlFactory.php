<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreateMandateRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Feedbacks;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\OrderReferences;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProductFilter;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5300SpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RedirectPaymentProduct5403SpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\SepaDirectDebitPaymentProduct771SpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\Worldline\Psr\Http\Message\UriInterface;
class HostedCheckoutUrlFactory
{
    private MerchantClientInterface $apiClient;
    private Transformer $requestTransformer;
    private ?UriInterface $notificationUrl;
    private bool $webhookModeIsAutomatic;
    private array $additionalWebhookUrls;
    public function __construct(MerchantClientInterface $apiClient, Transformer $requestTransformer, ?UriInterface $notificationUrl = null, bool $webhookModeIsAutomatic = \false, array $additionalWebhookUrls = [])
    {
        $this->apiClient = $apiClient;
        $this->requestTransformer = $requestTransformer;
        $this->notificationUrl = $notificationUrl;
        $this->webhookModeIsAutomatic = $webhookModeIsAutomatic;
        $this->additionalWebhookUrls = $additionalWebhookUrls;
    }
    /**
     * @throws Exception
     */
    public function create(HostedCheckoutInput $input) : CreateHostedCheckoutResponse
    {
        $request = $this->requestTransformer->create(CreateHostedCheckoutRequest::class, $input);
        if ($this->webhookModeIsAutomatic && $this->notificationUrl !== null) {
            $webhookUrls = [(string) $this->notificationUrl];
            foreach ($this->additionalWebhookUrls as $url) {
                $webhookUrls[] = $url;
            }
            $feedbacks = new Feedbacks();
            $feedbacks->setWebhooksUrls($webhookUrls);
            $request->setFeedbacks($feedbacks);
        }
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
        $sepaInput = $request->getSepaDirectDebitPaymentMethodSpecificInput();
        if (!$sepaInput) {
            $sepaInput = new SepaDirectDebitPaymentMethodSpecificInput();
        }
        $cvcoSpecificInput = new RedirectPaymentProduct5403SpecificInput();
        $cvcoSpecificInput->setCompleteRemainingPaymentAmount(\true);
        $redirectInput->setPaymentProduct5403SpecificInput($cvcoSpecificInput);
        $pledgSpecificInput = new RedirectPaymentProduct5300SpecificInput();
        $redirectInput->setPaymentProduct5300SpecificInput($pledgSpecificInput);
        $request->setRedirectPaymentMethodSpecificInput($redirectInput);
        $sepaSpecificInput = $sepaInput->getPaymentProduct771SpecificInput();
        if (!$sepaSpecificInput) {
            $sepaSpecificInput = new SepaDirectDebitPaymentProduct771SpecificInput();
        }
        $settings = \get_option('woocommerce_worldline-for-woocommerce_settings', []);
        $signatureTypeSetting = isset($settings['sdd_signature_type']) ? (string) $settings['sdd_signature_type'] : 'SMS';
        $signatureType = $signatureTypeSetting === 'UNSIGNED' ? 'UNSIGNED' : 'SMS';
        $uniqueReference = $request->getOrder()->getReferences()->getMerchantReference() ?? '';
        $locale = 'en_US';
        if ($request->getHostedCheckoutSpecificInput() && $request->getHostedCheckoutSpecificInput()->getLocale()) {
            $locale = (string) $request->getHostedCheckoutSpecificInput()->getLocale();
        }
        $language = $this->mapLocaleToLanguage($locale);
        $mandate = new CreateMandateRequest();
        $customerReference = 'CustomerRef_' . \time();
        $mandate->setCustomerReference($customerReference);
        $mandate->setRecurrenceType('UNIQUE');
        $mandate->setSignatureType($signatureType);
        $mandate->setLanguage($language);
        if ($uniqueReference !== '') {
            $mandate->setUniqueMandateReference($uniqueReference);
        }
        $sepaSpecificInput->setMandate($mandate);
        $sepaInput->setPaymentProduct771SpecificInput($sepaSpecificInput);
        $request->setSepaDirectDebitPaymentMethodSpecificInput($sepaInput);
        $modifier = $input->hostedCheckoutRequestModifier();
        $settings = \get_option('woocommerce_worldline-for-woocommerce_settings', []);
        $descriptorSetting = $settings['fixed_soft_descriptor'] ?: null;
        $references = $request->getOrder()->getReferences() ?? new OrderReferences();
        $references->setDescriptor($modifier === null ? $descriptorSetting : null);
        $request->getOrder()->setReferences($references);
        if (!\is_null($modifier)) {
            $request = $modifier->modify($request, $input);
        }
        $hostedCheckoutClient = $this->apiClient->hostedCheckout();
        return $hostedCheckoutClient->createHostedCheckout($request);
    }
    /**
     * Allowed by business: de, en, es, fr, it, nl, si, sk, sv
     */
    private function mapLocaleToLanguage(string $locale) : string
    {
        $lang = \strtolower(\substr($locale, 0, 2));
        $allowed = ['de', 'en', 'es', 'fr', 'it', 'nl', 'si', 'sk', 'sv'];
        return \in_array($lang, $allowed, \true) ? $lang : 'en';
    }
}
