<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Adapter
 */

namespace MoptWorldline\Adapter;

use Monolog\Level;
use MoptWorldline\Controller\Payment\ReturnUrlController;
use MoptWorldline\MoptWorldline;
use MoptWorldline\Service\DiscountHelper;
use MoptWorldline\Service\LocaleHelper;
use MoptWorldline\Service\LogHelper;
use MoptWorldline\Service\OrderHelper;
use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentProducts;
use OnlinePayments\Sdk\DataObject;
use OnlinePayments\Sdk\Domain\AddressPersonal;
use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\BrowserData;
use OnlinePayments\Sdk\Domain\CancelPaymentRequest;
use OnlinePayments\Sdk\Domain\CancelPaymentResponse;
use OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use OnlinePayments\Sdk\Domain\CaptureResponse;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInputForHostedCheckout;
use OnlinePayments\Sdk\Domain\ContactDetails;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\CreateHostedTokenizationRequest;
use OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use OnlinePayments\Sdk\Domain\CreatePaymentResponse;
use OnlinePayments\Sdk\Domain\Customer;
use OnlinePayments\Sdk\Domain\CustomerDevice;
use OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\LineItem;
use OnlinePayments\Sdk\Domain\MerchantAction;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\OrderLineDetails;
use OnlinePayments\Sdk\Domain\OrderReferences;
use OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\PaymentReferences;
use OnlinePayments\Sdk\Domain\PersonalInformation;
use OnlinePayments\Sdk\Domain\PersonalName;
use OnlinePayments\Sdk\Domain\RedirectData;
use OnlinePayments\Sdk\Domain\RedirectionData;
use OnlinePayments\Sdk\Domain\RedirectPaymentMethodSpecificInput;
use OnlinePayments\Sdk\Domain\RefundRequest;
use OnlinePayments\Sdk\Domain\RefundResponse;
use OnlinePayments\Sdk\Domain\Shipping;
use OnlinePayments\Sdk\Domain\ShoppingCart;
use OnlinePayments\Sdk\Domain\ShoppingCartExtension;
use OnlinePayments\Sdk\Domain\ThreeDSecure;
use OnlinePayments\Sdk\Merchant\MerchantClient;
use Shopware\Core\Checkout\Cart\Price\Struct\CalculatedPrice;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderCustomer\OrderCustomerEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemCollection;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptWorldline\Bootstrap\Form;
use OnlinePayments\Sdk\DefaultConnection;
use OnlinePayments\Sdk\CommunicatorConfiguration;
use OnlinePayments\Sdk\Communicator;
use OnlinePayments\Sdk\Client;
use OnlinePayments\Sdk\Merchant\Products\GetPaymentProductsParams;
use OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use OnlinePayments\Sdk\Domain\PaymentProduct130SpecificThreeDSecure;
use OnlinePayments\Sdk\Domain\RedirectPaymentProduct5408SpecificInput;

/**
 * This is the adaptor for Worldline's API
 *
 * @author Mediaopt GmbH
 * @package MoptWorldline\Adapter
 */
class WorldlineSDKAdapter
{
    const HOSTED_TOKENIZATION_URL_PREFIX = 'https://payment.';

    const LIVE_ENDPOINT = "https://payment.direct.worldline-solutions.com";
    const TEST_ENDPOINT = "https://payment.preprod.direct.worldline-solutions.com";

    /** @var string */
    const INTEGRATOR_NAME = 'Mediaopt';
    const SHIPPING_LABEL = 'Shipping';
    const REQUEST_POSTFIX = '_0';

    /** @var MerchantClient */
    protected $merchantClient;

    /** @var SystemConfigService */
    private $systemConfigService;

    /** @var string|null */
    private $salesChannelId;

    /**
     * @param SystemConfigService $systemConfigService
     * @param string|null $salesChannelId
     */
    public function __construct(SystemConfigService $systemConfigService, ?string $salesChannelId = null)
    {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelId = $salesChannelId;
    }

    /**
     * @param array $credentials
     * @return MerchantClient
     * @throws \Exception
     */
    public function getMerchantClient(array $credentials = []): MerchantClient
    {
        if ($this->merchantClient !== null) {
            return $this->merchantClient;
        }

        if (empty($credentials)) {
            $credentials = $this->getCredentials();
        }

        $shoppingCartExtension = new ShoppingCartExtension(
            MoptWorldline::PLUGIN_CREATOR,
            MoptWorldline::PLUGIN_NAME,
            MoptWorldline::PLUGIN_VERSION,
            MoptWorldline::PLUGIN_ID
        );

        $communicatorConfiguration = new CommunicatorConfiguration(
            $credentials['apiKey'],
            $credentials['apiSecret'],
            $credentials['endpoint'] ?: ($credentials['isLiveMode'] ? self::LIVE_ENDPOINT : self::TEST_ENDPOINT),
            self::INTEGRATOR_NAME . ' ' . MoptWorldline::PLUGIN_VERSION,
            null
        );

        $communicatorConfiguration->setShoppingCartExtension($shoppingCartExtension);

        $connection = new DefaultConnection();
        $communicator = new Communicator($connection, $communicatorConfiguration);
        $client = new Client($communicator);
        $this->merchantClient = $client->merchant($credentials['merchantId']);

        return $this->merchantClient;
    }

    /**
     * @param string $countryIso3
     * @param string $currencyIsoCode
     * @return GetPaymentProductsResponse
     * @throws \Exception
     */
    public function getPaymentProducts(string $countryIso3, string $currencyIsoCode): GetPaymentProductsResponse
    {
        $queryParams = new GetPaymentProductsParams();

        $queryParams->setCountryCode($countryIso3);
        $queryParams->setCurrencyCode($currencyIsoCode);
        return $this->merchantClient
            ->products()
            ->getPaymentProducts($queryParams);
    }

    /**
     * @param string $hostedCheckoutId
     * @return PaymentDetailsResponse
     * @throws \Exception
     */
    public function getPaymentDetails(string $hostedCheckoutId): PaymentDetailsResponse
    {
        $merchantClient = $this->getMerchantClient();
        $hostedCheckoutId = $hostedCheckoutId . self::REQUEST_POSTFIX;
        return $merchantClient->payments()->getPaymentDetails($hostedCheckoutId);
    }

    /**
     * @param int $amountTotal
     * @param string $currencyISO
     * @param string $worldlinePaymentProductId
     * @param OrderEntity|null $orderEntity
     * @param string $token
     * @param array $customerData
     * @return CreateHostedCheckoutResponse
     * @throws \Exception
     */
    public function createPayment(
        int          $amountTotal,
        string       $currencyISO,
        string       $worldlinePaymentProductId,
        ?OrderEntity $orderEntity,
        string       $token,
        array        $customerData
    ): CreateHostedCheckoutResponse
    {
        $fullRedirectTemplateName = $this->getPluginConfig(Form::FULL_REDIRECT_TEMPLATE_NAME);
        $merchantClient = $this->getMerchantClient();

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setCurrencyCode($currencyISO);
        $amountOfMoney->setAmount($amountTotal);

        $order = new Order();
        $order->setAmountOfMoney($amountOfMoney);

        if ($this->getPluginConfig(Form::ORDER_NUMBER_AS_REFERENCE_FIELD)) {
            $orderRef = new OrderReferences();
            $orderRef->setMerchantReference($orderEntity->getOrderNumber());
            $order->setReferences($orderRef);
        }

        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $ReturnUrlController = new ReturnUrlController($this->systemConfigService);
        $returnUrl = $ReturnUrlController->getReturnUrl($this, $this->isLiveMode());
        $hostedCheckoutSpecificInput->setReturnUrl($returnUrl);
        $hostedCheckoutSpecificInput->setLocale(OrderHelper::getLocale($orderEntity));
        $hostedCheckoutSpecificInput->setVariant($fullRedirectTemplateName);
        $cardPaymentMethodSpecificInput = new CardPaymentMethodSpecificInput();
        if ($this->isDirectSales()) {
            $cardPaymentMethodSpecificInput->setAuthorizationMode(Payment::DIRECT_SALE);
        }
        $groupCardsConfig = $this->getPluginConfig(Form::GROUP_CARDS);
        if ($groupCardsConfig) {
            $cardPaymentMethodSpecificInputForHostedCheckout = new CardPaymentMethodSpecificInputForHostedCheckout();
            $cardPaymentMethodSpecificInputForHostedCheckout->setGroupCards(true);
            $hostedCheckoutSpecificInput->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInputForHostedCheckout);
        }
        $hostedCheckoutRequest = new CreateHostedCheckoutRequest();
        if (!in_array($worldlinePaymentProductId, PaymentProducts::INTERNAL_PAYMENT_METHODS)) {
            $paymentProductFilter = new PaymentProductFilter();
            $paymentProductFilter->setProducts([$worldlinePaymentProductId]);

            $paymentProductFiltersHostedCheckout = new PaymentProductFiltersHostedCheckout();
            $paymentProductFiltersHostedCheckout->setRestrictTo($paymentProductFilter);
            $hostedCheckoutSpecificInput->setPaymentProductFilters($paymentProductFiltersHostedCheckout);
        }

        $this->setCustomProperties(
            $worldlinePaymentProductId,
            $currencyISO,
            $orderEntity,
            $cardPaymentMethodSpecificInput,
            $hostedCheckoutSpecificInput,
            $order,
            $hostedCheckoutRequest,
            $customerData,
        );

        if (!empty($token)) {
            $cardPaymentMethodSpecificInput->setToken($token);
        }

        $hostedCheckoutRequest->setOrder($order);
        $hostedCheckoutRequest->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);
        $hostedCheckoutRequest->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);
        $hostedCheckoutClient = $merchantClient->hostedCheckout();
        return $hostedCheckoutClient->createHostedCheckout($hostedCheckoutRequest);
    }

    /**
     * @param string $worldlinePaymentProductId
     * @param string $currencyISO
     * @param OrderEntity|null $orderEntity
     * @param CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput
     * @param HostedCheckoutSpecificInput $hostedCheckoutSpecificInput
     * @param Order $order
     * @param CreateHostedCheckoutRequest $hostedCheckoutRequest
     * @param array $customerData
     * @return void
     * @throws \Exception
     */
    private function setCustomProperties(
        string                         $worldlinePaymentProductId,
        string                         $currencyISO,
        ?OrderEntity                   $orderEntity,
        CardPaymentMethodSpecificInput &$cardPaymentMethodSpecificInput,
        HostedCheckoutSpecificInput    &$hostedCheckoutSpecificInput,
        Order                          &$order,
        CreateHostedCheckoutRequest    &$hostedCheckoutRequest,
        array $customerData,
    ): void
    {
        switch ($worldlinePaymentProductId) {
            case PaymentProducts::PAYMENT_PRODUCT_INTERSOLVE:
            {
                $cardPaymentMethodSpecificInput->setAuthorizationMode(Payment::DIRECT_SALE);
                $hostedCheckoutSpecificInput->setIsRecurring(false);
                break;
            }
            case PaymentProducts::PAYMENT_PRODUCT_KLARNA_PAY_NOW:
            case PaymentProducts::PAYMENT_PRODUCT_KLARNA_PAY_LATER:
            case PaymentProducts::PAYMENT_PRODUCT_TWINTWL:
            {
                $this->addCartToRequest(
                    $currencyISO, $orderEntity, $cardPaymentMethodSpecificInput, $hostedCheckoutSpecificInput, $order
                );
                $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
                $redirectPaymentMethodSpecificInput->setRequiresApproval(!$this->isDirectSales());
                $redirectPaymentMethodSpecificInput->setPaymentProductId($worldlinePaymentProductId);
                break;
            }
            case PaymentProducts::PAYMENT_PRODUCT_ONEY_3X_4X:
            case PaymentProducts::PAYMENT_PRODUCT_ONEY_FINANCEMENT_LONG:
            case PaymentProducts::PAYMENT_PRODUCT_ONEY_BRANDED_GIFT_CARD:
            {
                $this->addCartToRequest(
                    $currencyISO, $orderEntity, $cardPaymentMethodSpecificInput, $hostedCheckoutSpecificInput, $order
                );
                $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
                $redirectPaymentMethodSpecificInput->setPaymentProductId($worldlinePaymentProductId);
                $redirectPaymentMethodSpecificInput->setRequiresApproval(true);
                $redirectPaymentMethodSpecificInput->setPaymentOption($this->getPluginConfig(Form::ONEY_PAYMENT_OPTION_FIELD));
                break;
            }
            case PaymentProducts::PAYMENT_PRODUCT_PRZELEWY24:
            {
                $this->addCustomerEmail($orderEntity, $order);
                $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
                $redirectPaymentMethodSpecificInput->setPaymentProductId($worldlinePaymentProductId);
                $cardPaymentMethodSpecificInput = null;
                break;
            }
            case PaymentProducts::PAYMENT_PRODUCT_CARTE_BANCAIRE:
            {
                $this->addCarteBancaireData($orderEntity, $cardPaymentMethodSpecificInput);
                break;
            }
            case PaymentProducts::PAYMENT_PRODUCT_BANK_TRANSFER:
            {
                $instantPayment = $this->getPluginConfig(Form::BANK_TRANSFER_INSTANT_PAYMENT_FIELD);

                $specificInput = new RedirectPaymentProduct5408SpecificInput();
                $specificInput->setInstantPaymentOnly($instantPayment);

                $redirectionData = new RedirectionData();
                $redirectionData->setReturnUrl($hostedCheckoutSpecificInput->getReturnUrl());

                $cardPaymentMethodSpecificInput = null;

                $redirectPaymentMethodSpecificInput = new RedirectPaymentMethodSpecificInput();
                $redirectPaymentMethodSpecificInput->setPaymentProductId($worldlinePaymentProductId);
                $redirectPaymentMethodSpecificInput->setPaymentProduct5408SpecificInput($specificInput);
                $redirectPaymentMethodSpecificInput->setRedirectionData($redirectionData);
                break;
            }
            case Payment::FULL_REDIRECT_PAYMENT_METHOD_ID:
            case PaymentProducts::PAYMENT_PRODUCT_VISA: {
                $this->addCartToRequest(
                    $currencyISO, $orderEntity, $cardPaymentMethodSpecificInput, $hostedCheckoutSpecificInput, $order
                );
                $customer = $order->getCustomer();
                $customer->setDevice($this->getCustomerDevice($customerData));
                $order->setCustomer($customer);
                break;
            }
            default:
            {
                $this->addCustomerEmail($orderEntity, $order);
                break;
            }
        }

        if (isset($redirectPaymentMethodSpecificInput)) {
            $hostedCheckoutRequest->setRedirectPaymentMethodSpecificInput($redirectPaymentMethodSpecificInput);
        }
    }

    /**
     * @param string|null $token
     * @param string|null $localeId
     * @return string
     * @throws \Exception
     */
    public function createHostedTokenizationUrl(?string $token = null, ?string $localeId = ''): string
    {
        $iframeTemplateName = $this->getPluginConfig(Form::IFRAME_TEMPLATE_NAME);

        $merchantClient = $this->getMerchantClient();
        $hostedTokenizationClient = $merchantClient->hostedTokenization();
        $createHostedTokenizationRequest = new CreateHostedTokenizationRequest();
        $createHostedTokenizationRequest->setVariant($iframeTemplateName);
        $localeCode = LocaleHelper::getCode($localeId);
        $createHostedTokenizationRequest->setLocale($localeCode);
        if ($token) {
            $createHostedTokenizationRequest->setTokens($token);
        }

        $createHostedTokenizationResponse = $hostedTokenizationClient
            ->createHostedTokenization($createHostedTokenizationRequest);

        return self::HOSTED_TOKENIZATION_URL_PREFIX . $createHostedTokenizationResponse->getPartialRedirectUrl();
    }

    /**
     * @param array $customerData
     * @return GetHostedTokenizationResponse
     * @throws \Exception
     */
    public function createHostedTokenization(array $customerData): GetHostedTokenizationResponse
    {
        $merchantClient = $this->getMerchantClient();
        return $merchantClient->hostedTokenization()->getHostedTokenization($customerData[Form::WORLDLINE_CART_FORM_HOSTED_TOKENIZATION_ID]);
    }

    /**
     * @param int $amountTotal
     * @param string $currencyISO
     * @param array $customerData
     * @param GetHostedTokenizationResponse $hostedTokenization
     * @param ?OrderEntity $orderEntity
     * @return CreatePaymentResponse
     * @throws \Exception
     */
    public function createHostedTokenizationPayment(
        int                           $amountTotal,
        string                        $currencyISO,
        array                         $customerData,
        GetHostedTokenizationResponse $hostedTokenization,
        ?OrderEntity                  $orderEntity
    ): CreatePaymentResponse
    {
        $token = $hostedTokenization->getToken()->getId();
        $paymentProductId = $hostedTokenization->getToken()->getPaymentProductId();
        $merchantClient = $this->getMerchantClient();

        $customer = new Customer();
        $customer->setDevice($this->getCustomerDevice($customerData));

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setCurrencyCode($currencyISO);
        $amountOfMoney->setAmount($amountTotal);

        $order = new Order();
        $order->setAmountOfMoney($amountOfMoney);
        $order->setCustomer($customer);

        $ReturnUrlController = new ReturnUrlController($this->systemConfigService);
        $returnUrl = $ReturnUrlController->getReturnUrl($this, $this->isLiveMode());
        $redirectionData = new RedirectionData();
        $redirectionData->setReturnUrl($returnUrl);

        $threeDSecure = new ThreeDSecure();
        $threeDSecure->setRedirectionData($redirectionData);
        $threeDSecure->setChallengeIndicator('challenge-required');

        $cardPaymentMethodSpecificInput = new CardPaymentMethodSpecificInput();
        $cardPaymentMethodSpecificInput->setAuthorizationMode(Payment::FINAL_AUTHORIZATION);
        if ($this->isDirectSales()) {
            $cardPaymentMethodSpecificInput->setAuthorizationMode(Payment::DIRECT_SALE);
        }
        $cardPaymentMethodSpecificInput->setToken($token);
        $cardPaymentMethodSpecificInput->setPaymentProductId($paymentProductId);
        $cardPaymentMethodSpecificInput->setTokenize(false);
        $cardPaymentMethodSpecificInput->setThreeDSecure($threeDSecure);
        $cardPaymentMethodSpecificInput->setReturnUrl($returnUrl);

        $createPaymentRequest = new CreatePaymentRequest();
        $createPaymentRequest->setOrder($order);
        $createPaymentRequest->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);

        if ($paymentProductId == PaymentProducts::PAYMENT_PRODUCT_CARTE_BANCAIRE) {
            $this->addCarteBancaireData($orderEntity, $cardPaymentMethodSpecificInput);
        }

        // Get the response for the PaymentsClient
        $paymentsClient = $merchantClient->payments();
        $createPaymentResponse = $paymentsClient->createPayment($createPaymentRequest);
        $this->setRedirectUrl($createPaymentResponse, $returnUrl);
        return $createPaymentResponse;
    }

    /**
     * @param CreatePaymentResponse $createPaymentResponse
     * @param string $returnUrl
     * @return void
     */
    private function setRedirectUrl(CreatePaymentResponse &$createPaymentResponse, string $returnUrl): void
    {
        if ($createPaymentResponse->getMerchantAction()) {
            return;
        }

        $paymentId = $createPaymentResponse->getPayment()->getId();
        $redirectData = new RedirectData();
        $returnUrlParams = ['paymentId' => $paymentId];
        $redirectUrl = $returnUrl . "?" . http_build_query($returnUrlParams);
        $redirectData->setRedirectURL($redirectUrl);
        $merchantAction = new MerchantAction();
        $merchantAction->setRedirectData($redirectData);
        $createPaymentResponse->setMerchantAction($merchantAction);
    }

    /**
     * @param string $hostedCheckoutId
     * @param int $amount
     * @param bool $isFinal
     * @return CaptureResponse
     * @throws \Exception
     */
    public function capturePayment(string $hostedCheckoutId, int $amount, bool $isFinal): CaptureResponse
    {
        $merchantClient = $this->getMerchantClient();
        $hostedCheckoutId = $hostedCheckoutId . self::REQUEST_POSTFIX;

        $capturePaymentRequest = new CapturePaymentRequest();
        $capturePaymentRequest->setAmount($amount);
        $capturePaymentRequest->setIsFinal($isFinal);

        return $merchantClient->payments()->capturePayment($hostedCheckoutId, $capturePaymentRequest);
    }

    /**
     * @param string $hostedCheckoutId
     * @param int $amount
     * @param string $currency
     * @param bool $isFinal
     * @return CancelPaymentResponse
     * @throws \Exception
     */
    public function cancelPayment(string $hostedCheckoutId, int $amount, string $currency, bool $isFinal): CancelPaymentResponse
    {
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($amount);
        $amountOfMoney->setCurrencyCode($currency);

        $cancelRequest = new CancelPaymentRequest();
        $cancelRequest->setAmountOfMoney($amountOfMoney);
        $cancelRequest->setIsFinal($isFinal);

        $merchantClient = $this->getMerchantClient();
        $hostedCheckoutId = $hostedCheckoutId . self::REQUEST_POSTFIX;
        return $merchantClient->payments()->cancelPayment($hostedCheckoutId, $cancelRequest);
    }

    /**
     * @param string $hostedCheckoutId
     * @param int $amount
     * @param string $currency
     * @param string $orderNumber
     * @return RefundResponse
     * @throws \Exception
     */
    public function refundPayment(string $hostedCheckoutId, int $amount, string $currency, string $orderNumber): RefundResponse
    {
        $merchantClient = $this->getMerchantClient();
        $hostedCheckoutId = $hostedCheckoutId . self::REQUEST_POSTFIX;

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($amount);
        $amountOfMoney->setCurrencyCode($currency);

        $paymentReferences = new PaymentReferences();
        $paymentReferences->setMerchantReference($orderNumber);

        $refundRequest = new RefundRequest();
        $refundRequest->setAmountOfMoney($amountOfMoney);
        $refundRequest->setReferences($paymentReferences);

        return $merchantClient->payments()->refundPayment($hostedCheckoutId, $refundRequest);
    }

    /**
     * @param string $token
     * @return DataObject|null
     * @throws \Exception
     */
    public function deleteToken(string $token): ?DataObject
    {
        $merchantClient = $this->getMerchantClient();
        return $merchantClient->tokens()->deleteToken($token);
    }

    /**
     * @param PaymentDetailsResponse $paymentDetails
     * @return int
     */
    public function getStatus(PaymentDetailsResponse $paymentDetails): int
    {
        if (!is_object($paymentDetails)
            || !is_object($paymentDetails->getStatusOutput())
            || is_null($paymentDetails->getStatusOutput()->getStatusCode())
        ) {
            return 0;
        }
        return $paymentDetails->getStatusOutput()->getStatusCode();
    }

    /**
     * @param PaymentDetailsResponse $paymentDetails
     * @return string
     */
    public function getRedirectToken(PaymentDetailsResponse $paymentDetails): string
    {
        if (!is_object($paymentDetails)
            || !is_object($paymentDetails->getPaymentOutput())
            || !is_object($paymentDetails->getPaymentOutput()->getCardPaymentMethodSpecificOutput())
            || is_null($paymentDetails->getPaymentOutput()->getCardPaymentMethodSpecificOutput()->getToken())
        ) {
            return '';
        }
        return $paymentDetails->getPaymentOutput()->getCardPaymentMethodSpecificOutput()->getToken();
    }


    /**
     * @param CancelPaymentResponse $cancelPaymentResponse
     * @return int
     */
    public function getCancelStatus(CancelPaymentResponse $cancelPaymentResponse): int
    {
        if (!is_object($cancelPaymentResponse)
            || !is_object($cancelPaymentResponse->getPayment())
            || is_null($cancelPaymentResponse->getPayment()->getStatusOutput())
            || is_null($cancelPaymentResponse->getPayment()->getStatusOutput()->getStatusCode())
        ) {
            return 0;
        }
        return $cancelPaymentResponse->getPayment()->getStatusOutput()->getStatusCode();
    }

    /**
     * @param RefundResponse $refundResponse
     * @return int
     */
    public function getRefundStatus(RefundResponse $refundResponse): int
    {
        if (!is_object($refundResponse)
            || !is_object($refundResponse->getStatusOutput())
            || is_null($refundResponse->getStatusOutput()->getStatusCode())
        ) {
            return 0;
        }
        return $refundResponse->getStatusOutput()->getStatusCode();
    }

    /**
     * @return array
     */
    private function getCredentials(): array
    {
        $isLiveMode = $this->isLiveMode();
        if ($isLiveMode) {
            return [
                'merchantId' => $this->getPluginConfig(Form::LIVE_MERCHANT_ID_FIELD),
                'apiKey' => $this->getPluginConfig(Form::LIVE_API_KEY_FIELD),
                'apiSecret' => $this->getPluginConfig(Form::LIVE_API_SECRET_FIELD),
                'endpoint' => $this->getPluginConfig(Form::LIVE_ENDPOINT_FIELD),
                'isLiveMode' => $isLiveMode,
            ];
        }
        return [
            'merchantId' => $this->getPluginConfig(Form::MERCHANT_ID_FIELD),
            'apiKey' => $this->getPluginConfig(Form::API_KEY_FIELD),
            'apiSecret' => $this->getPluginConfig(Form::API_SECRET_FIELD),
            'endpoint' => $this->getPluginConfig(Form::SANDBOX_ENDPOINT_FIELD),
            'isLiveMode' => $isLiveMode,
        ];
    }

    /**
     * @return array
     */
    public function getWebhookCredentials(): array
    {
        if ($this->isLiveMode()) {
            $webhookKey = $this->getPluginConfig(Form::LIVE_WEBHOOK_KEY_FIELD);
            $webhookSecret = $this->getPluginConfig(Form::LIVE_WEBHOOK_SECRET_FIELD);
            return [$webhookKey => $webhookSecret];
        }
        $webhookKey = $this->getPluginConfig(Form::WEBHOOK_KEY_FIELD);
        $webhookSecret = $this->getPluginConfig(Form::WEBHOOK_SECRET_FIELD);
        return [$webhookKey => $webhookSecret];
    }

    /**
     * @return bool
     */
    public function isLiveMode(): bool
    {
        return (bool)$this->getPluginConfig(Form::IS_LIVE_MODE_FIELD);
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getPluginConfig(string $key)
    {
        return $this->systemConfigService->get($key, $this->salesChannelId);
    }

    /**
     * @param string $currencyISO
     * @param OrderEntity $orderEntity
     * @param CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput
     * @param HostedCheckoutSpecificInput $hostedCheckoutSpecificInput
     * @param Order $order
     * @return void
     * @throws \Exception
     */
    private function addCartToRequest(
        string                         $currencyISO,
        OrderEntity                    $orderEntity,
        CardPaymentMethodSpecificInput &$cardPaymentMethodSpecificInput,
        HostedCheckoutSpecificInput    $hostedCheckoutSpecificInput,
        Order                          $order
    ): void
    {
        $shipping = new Shipping();
        $shipping->setAddress($this->createAddress($orderEntity->getDeliveries()->getShippingAddress()->first()));

        $shoppingCart = new ShoppingCart();
        $isNetPrice = !$orderEntity->getOrderCustomer()->getCustomer()->getGroup()->getDisplayGross();

        $shoppingCart->setItems(
            $this->createRequestLineItems(
                $orderEntity->getLineItems(),
                $orderEntity->getShippingCosts(),
                $currencyISO,
                $isNetPrice
            )
        );
        $shipping->setShippingCost($orderEntity->getShippingCosts()->getTotalPrice() * 100);

        $order->setShoppingCart($shoppingCart);
        $order->setShipping($shipping);
        $order->setCustomer(
            $this->createCustomer(
                $orderEntity->getOrderCustomer(),
                $orderEntity->getBillingAddress()
            )
        );

        $hostedCheckoutSpecificInput->setPaymentProductFilters(null);
        $hostedCheckoutSpecificInput->setVariant(null);

        $cardPaymentMethodSpecificInput = null;
    }

    /**
     * @param OrderEntity $orderEntity
     * @param Order $order
     * @return void
     */
    private function addCustomerEmail(OrderEntity $orderEntity, Order $order): void
    {
        $orderCustomer = $orderEntity->getOrderCustomer();
        $contactDetails = new ContactDetails();
        $contactDetails->setEmailAddress($orderCustomer->getEmail());

        $customer = new Customer();
        $customer->setContactDetails($contactDetails);

        $order->setCustomer($customer);
    }

    /**
     * @param OrderEntity $orderEntity
     * @param CardPaymentMethodSpecificInput $cardPaymentMethodSpecificInput
     * @return void
     */
    private function addCarteBancaireData(
        OrderEntity $orderEntity,
        CardPaymentMethodSpecificInput &$cardPaymentMethodSpecificInput
    ): void
    {
        $count = 0;
        foreach ($orderEntity->getLineItems() as $lineItem) {
            $count += $lineItem->getQuantity();
        }
        $useCase = $this->isDirectSales() ? 'single-amount' : 'payment-upon-shipment';
        $threeDSecure = new PaymentProduct130SpecificThreeDSecure();
        $threeDSecure->setUsecase($useCase);
        $threeDSecure->setNumberOfItems(min($count, 99));
        $cardPaymentMethodSpecificInput->setPaymentProduct130SpecificInput(
            $threeDSecure
        );
    }

    /**
     * @param OrderLineItemCollection $lineItemCollection
     * @param CalculatedPrice $shippingPrice
     * @param string $currencyISO
     * @param bool $isNetPrice
     * @return array
     * @throws \Exception
     */
    private function createRequestLineItems(
        OrderLineItemCollection $lineItemCollection,
        CalculatedPrice         $shippingPrice,
        string                  $currencyISO,
        bool                    $isNetPrice
    ): array
    {
        $requestLineItems = [];
        $discount = 0;
        $maxPrices = [
            'unit' => ['id' => '', 'price' => 0],
            'item' => ['id' => '', 'price' => 0]
        ];
        $grandPrice = 0;
        $grandCount = 0;
        /** @var OrderLineItemEntity $lineItem */
        foreach ($lineItemCollection as $lineItem) {
            [$totalPrice, $quantity, $unitPrice] = self::getUnitPrice($lineItem, $isNetPrice);
            if ($totalPrice < 0) {
                $discount += abs($totalPrice);
                continue;
            }
            $grandPrice += $totalPrice;
            $grandCount += $quantity;

            if ($maxPrices['unit']['price'] < $unitPrice) {
                $maxPrices['unit']['price'] = $unitPrice;
                $maxPrices['unit']['id'] = $lineItem->getId();
            }
            if ($maxPrices['item']['price'] < $totalPrice) {
                $maxPrices['item']['price'] = $totalPrice;
                $maxPrices['item']['id'] = $lineItem->getId();
            }
            $requestLineItems[$lineItem->getId()] = self::createLineItem($lineItem->getLabel(), $currencyISO, $totalPrice, $unitPrice, $quantity);
        }

        $shippingPrice = self::getShippingPrice($shippingPrice, $isNetPrice);
        if ($shippingPrice > 0) {
            $grandPrice += $shippingPrice;
            $grandCount++;
        }

        if ($discount > 0) {
            if ($grandPrice <= ($discount + $grandCount)) {
                LogHelper::addLog(Level::Error, 'Discount over limit.');
                throw new \Exception(
                    'Discount should be less than ' . ($grandPrice - $grandCount) / 100
                );
            }

            $requestLineItems = DiscountHelper::handleDiscount($requestLineItems, $discount, $maxPrices);
        }

        return $requestLineItems;
    }

    /**
     * @param string $label
     * @param string $currencyISO
     * @param int $totalPrice
     * @param int $unitPrice
     * @param int $quantity
     * @param int $discount
     * @return LineItem
     */
    public static function createLineItem(
        string $label,
        string $currencyISO,
        int    $totalPrice,
        int    $unitPrice,
        int    $quantity,
        int    $discount = 0
    ): LineItem
    {
        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setCurrencyCode($currencyISO);
        $amountOfMoney->setAmount($totalPrice);

        $lineDetails = new OrderLineDetails();
        $lineDetails->setProductName($label);
        $lineDetails->setProductPrice($unitPrice);
        $lineDetails->setQuantity($quantity);
        $lineDetails->setDiscountAmount($discount);
        $lineDetails->setTaxAmount(0);

        $lineItem = new LineItem();
        $lineItem->setAmountOfMoney($amountOfMoney);
        $lineItem->setOrderLineDetails($lineDetails);
        return $lineItem;
    }

    /**
     * @param OrderAddressEntity $addressEntity
     * @return AddressPersonal
     */
    private function createAddress(OrderAddressEntity $addressEntity): AddressPersonal
    {
        $name = new PersonalName();
        $name->setFirstName($addressEntity->getFirstName());
        $name->setSurname($addressEntity->getLastName());
        $name->setTitle($addressEntity->getTitle());

        $address = new AddressPersonal();
        $address->setStreet($addressEntity->getStreet());
        $address->setZip($addressEntity->getZipcode());
        $address->setCity($addressEntity->getCity());
        $address->setCountryCode($addressEntity->getCountry()->getIso());
        $address->setName($name);

        return $address;
    }

    /**
     * @param OrderCustomerEntity $orderCustomer
     * @param OrderAddressEntity $billingAddress
     * @return Customer
     */
    private function createCustomer(OrderCustomerEntity $orderCustomer, OrderAddressEntity $billingAddress): Customer
    {
        $personalName = new PersonalName();
        $personalName->setFirstName($orderCustomer->getCustomer()->getFirstName());
        $personalName->setSurname($orderCustomer->getCustomer()->getLastName());
        $personalName->setTitle($orderCustomer->getCustomer()->getTitle());

        $contactDetails = new ContactDetails();
        $contactDetails->setEmailAddress($orderCustomer->getEmail());

        $personalInformation = new PersonalInformation();
        $personalInformation->setName($personalName);

        $customer = new Customer();
        $customer->setContactDetails($contactDetails);
        $customer->setPersonalInformation($personalInformation);
        $customer->setBillingAddress($this->createAddress($billingAddress));
        return $customer;
    }

    /**
     * @return bool
     */
    public function isDirectSales(): bool
    {
        return $this->getPluginConfig(Form::AUTO_CAPTURE) === Form::AUTO_CAPTURE_IMMEDIATELY;
    }

    /**
     * @param OrderLineItemEntity $lineItem
     * @param bool $isNetPrice
     * @return array
     */
    public static function getUnitPrice(OrderLineItemEntity $lineItem, bool $isNetPrice): array
    {
        $tax = 0;
        if ($isNetPrice) {
            $tax += $lineItem->getPrice()->getCalculatedTaxes()->getAmount();
        }
        $totalPrice = (int)round((($lineItem->getPrice()->getTotalPrice() + $tax) * 100));
        $quantity = $lineItem->getPrice()->getQuantity();

        return [
            $totalPrice,
            $quantity,
            $totalPrice / $quantity
        ];
    }

    /**
     * @param CalculatedPrice $shippingPrice
     * @param bool $isNetPrice
     * @return int
     */
    public static function getShippingPrice(CalculatedPrice $shippingPrice, bool $isNetPrice): int
    {
        $shippingTaxTotal = 0;
        if ($isNetPrice) {
            foreach ($shippingPrice->getCalculatedTaxes()->getElements() as $shippingTax) {
                $shippingTaxTotal += $shippingTax->getTax();
            }
        }

        return (int)(round(($shippingPrice->getTotalPrice() + $shippingTaxTotal) * 100));
    }

    /**
     * @param array $customerData
     * @return CustomerDevice
     */
    private function getCustomerDevice(array $customerData): CustomerDevice
    {
        $browserData = new BrowserData();
        $browserData->setColorDepth($customerData[Form::WORLDLINE_CART_FORM_BROWSER_DATA_COLOR_DEPTH]);
        $browserData->setJavaEnabled($customerData[Form::WORLDLINE_CART_FORM_BROWSER_DATA_JAVA_ENABLED]);
        $browserData->setScreenHeight($customerData[Form::WORLDLINE_CART_FORM_BROWSER_DATA_SCREEN_HEIGHT]);
        $browserData->setScreenWidth($customerData[Form::WORLDLINE_CART_FORM_BROWSER_DATA_SCREEN_WIDTH]);

        $customerDevice = new CustomerDevice();
        $customerDevice->setLocale($customerData[Form::WORLDLINE_CART_FORM_LOCALE]);
        $customerDevice->setTimezoneOffsetUtcMinutes($customerData[Form::WORLDLINE_CART_FORM_TIMEZONE_OFFSET_MINUTES]);
        $customerDevice->setAcceptHeader("*\/*");
        $customerDevice->setUserAgent($customerData[Form::WORLDLINE_CART_FORM_USER_AGENT]);
        $customerDevice->setBrowserData($browserData);
        $customerDevice->setIpAddress($this->getIp());

        return $customerDevice;
    }


    private function getIp()
    {
        $ip = '0.0.0.0';
        if (array_key_exists('HTTP_X_REAL_IP', $_SERVER)) {
            $ip = explode(':', $_SERVER['HTTP_X_REAL_IP'])[0];
        } elseif(array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
            $ip = explode(':', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
        }
        return $ip;
    }
}
