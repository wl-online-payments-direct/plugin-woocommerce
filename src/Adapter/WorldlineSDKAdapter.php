<?php

namespace MoptWorldline\Adapter;

use Monolog\Logger;
use OnlinePayments\Sdk\Domain\AmountOfMoney;
use OnlinePayments\Sdk\Domain\CancelPaymentResponse;
use OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use OnlinePayments\Sdk\Domain\CaptureResponse;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutRequest;
use OnlinePayments\Sdk\Domain\HostedCheckoutSpecificInput;
use OnlinePayments\Sdk\Domain\Order;
use OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use OnlinePayments\Sdk\Domain\PaymentProductFilter;
use OnlinePayments\Sdk\Domain\PaymentProductFiltersHostedCheckout;
use OnlinePayments\Sdk\Domain\PaymentReferences;
use OnlinePayments\Sdk\Domain\RefundRequest;
use OnlinePayments\Sdk\Domain\RefundResponse;
use OnlinePayments\Sdk\Merchant\MerchantClient;
use Shopware\Core\Kernel;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptWorldline\Bootstrap\Form;
use OnlinePayments\Sdk\DefaultConnection;
use OnlinePayments\Sdk\CommunicatorConfiguration;
use OnlinePayments\Sdk\Communicator;
use OnlinePayments\Sdk\Client;
use OnlinePayments\Sdk\Merchant\Products\GetPaymentProductsParams;
use OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;

/**
 * This is the adaptor for Worldline's API
 *
 * @author Mediaopt GmbH
 * @package MoptWorldline\Adapter
 */
class WorldlineSDKAdapter
{
    /** @var string */
    const INTEGRATOR_NAME = 'Mediaopt';

    /** @var MerchantClient */
    protected $merchantClient;

    /** @var SystemConfigService */
    private $systemConfigService;

    /** @var Logger */
    private $logger;

    /** @var string|null */
    private $salesChannelId;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     * @param string|null $salesChannelId
     */
    public function __construct(SystemConfigService $systemConfigService, Logger $logger, ?string $salesChannelId = null)
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
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
            $credentials = [
                'merchantId' => $this->getPluginConfig(Form::MERCHANT_ID_FIELD),
                'apiKey' => $this->getPluginConfig(Form::API_KEY_FIELD),
                'apiSecret' => $this->getPluginConfig(Form::API_SECRET_FIELD),
                'isLiveMode' => (bool)$this->getPluginConfig(Form::IS_LIVE_MODE_FIELD),
            ];
        }
        $credentials['endpoint'] = $this->getEndpoint($credentials['isLiveMode']);

        $communicatorConfiguration = new CommunicatorConfiguration(
            $credentials['apiKey'],
            $credentials['apiSecret'],
            $credentials['endpoint'],
            self::INTEGRATOR_NAME,
            null
        );

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
    public function getPaymentMethods(string $countryIso3, string $currencyIsoCode): GetPaymentProductsResponse
    {
        $queryParams = new GetPaymentProductsParams();

        $queryParams->setCountryCode($countryIso3);
        $queryParams->setCurrencyCode($currencyIsoCode);
        return $this->merchantClient
            ->products()
            ->getPaymentProducts($queryParams);
    }

    /**
     * @return void
     * @throws \Exception
     */
    public function testConnection()
    {
        $queryParams = new GetPaymentProductsParams();

        $queryParams->setCountryCode("DEU");
        $queryParams->setCurrencyCode("EUR");

        $this->merchantClient
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
        $hostedCheckoutId = $hostedCheckoutId . '_0';
        return $merchantClient->payments()->getPaymentDetails($hostedCheckoutId);
    }

    /**
     * @param float $amountTotal
     * @param string $currencyISO
     * @param int $worldlinePaymentMethodId
     * @return CreateHostedCheckoutResponse
     * @throws \Exception
     */
    public function createPayment(float $amountTotal, string $currencyISO, int $worldlinePaymentMethodId): CreateHostedCheckoutResponse
    {
        $merchantClient = $this->getMerchantClient();

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setCurrencyCode($currencyISO);
        $amountOfMoney->setAmount($amountTotal * 100);

        $order = new Order();
        $order->setAmountOfMoney($amountOfMoney);

        $hostedCheckoutSpecificInput = new HostedCheckoutSpecificInput();
        $returnUrl = $this->getPluginConfig(Form::RETURN_URL_FIELD);
        $hostedCheckoutSpecificInput->setReturnUrl($returnUrl);

        if ($worldlinePaymentMethodId != 0) {
            $paymentProductFilter= new PaymentProductFilter();
            $paymentProductFilter->setProducts([$worldlinePaymentMethodId]);

            $paymentProductFiltersHostedCheckout = new PaymentProductFiltersHostedCheckout();
            $paymentProductFiltersHostedCheckout->setRestrictTo($paymentProductFilter);
            $hostedCheckoutSpecificInput->setPaymentProductFilters($paymentProductFiltersHostedCheckout);
        }

        $hostedCheckoutRequest = new CreateHostedCheckoutRequest();
        $hostedCheckoutRequest->setOrder($order);
        $hostedCheckoutRequest->setHostedCheckoutSpecificInput($hostedCheckoutSpecificInput);

        $hostedCheckoutClient = $merchantClient->hostedCheckout();
        return $hostedCheckoutClient->createHostedCheckout($hostedCheckoutRequest);
    }

    /**
     * @param string $hostedCheckoutId
     * @param float $amount
     * @return void
     * @throws \Exception
     */
    public function capturePayment(string $hostedCheckoutId, float $amount): CaptureResponse
    {
        $merchantClient = $this->getMerchantClient();
        $hostedCheckoutId = $hostedCheckoutId . '_0';

        $capturePaymentRequest = new CapturePaymentRequest();
        $capturePaymentRequest->setAmount($amount * 100);
        $capturePaymentRequest->setIsFinal(true);

        return $merchantClient->payments()->capturePayment($hostedCheckoutId, $capturePaymentRequest);
    }

    /**
     * @param string $hostedCheckoutId
     * @return CancelPaymentResponse
     * @throws \Exception
     */
    public function cancelPayment(string $hostedCheckoutId): CancelPaymentResponse
    {
        $merchantClient = $this->getMerchantClient();
        $hostedCheckoutId = $hostedCheckoutId . '_1';
        return $merchantClient->payments()->cancelPayment($hostedCheckoutId);
    }

    /**
     * @param string $hostedCheckoutId
     * @param float $amount
     * @param string $currency
     * @param string $orderNumber
     * @return RefundResponse
     * @throws \Exception
     */
    public function refundPayment(string $hostedCheckoutId, float $amount, string $currency, string $orderNumber): RefundResponse
    {
        $merchantClient = $this->getMerchantClient();
        $hostedCheckoutId = $hostedCheckoutId . '_1';

        $amountOfMoney = new AmountOfMoney();
        $amountOfMoney->setAmount($amount * 100);
        $amountOfMoney->setCurrencyCode($currency);

        $paymentReferences = new PaymentReferences();
        $paymentReferences->setMerchantReference($orderNumber);

        $refundRequest = new RefundRequest();
        $refundRequest->setAmountOfMoney($amountOfMoney);
        $refundRequest->setReferences($paymentReferences);

        return $merchantClient->payments()->refundPayment($hostedCheckoutId, $refundRequest);
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
     * @param bool $isLiveMode
     * @return string
     */
    private function getEndpoint(bool $isLiveMode): string
    {
        return $isLiveMode
            ? $this->getPluginConfig(Form::LIVE_ENDPOINT_FIELD)
            : $this->getPluginConfig(Form::SANDBOX_ENDPOINT_FIELD);
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
     * @param string $message
     * @param int $logLevel
     * @param mixed $additionalData
     * @return void
     */
    public function log(string $message, int $logLevel = 0, $additionalData = '')
    {
        if ($logLevel == 0) {
            $logLevel = $this->getLogLevel();
        }

        $this->logger->addRecord(
            $logLevel,
            $message,
            [
                'source' => 'Worldline',
                'additionalData' => json_encode($additionalData),
            ]
        );
    }

    /**
     * get monolog log-level by module configuration
     * @return int
     */
    protected function getLogLevel()
    {
        $logLevel = 'INFO';

        if ($overrideLogLevel = $this->getPluginConfig(Form::LOG_LEVEL)) {
            $logLevel = $overrideLogLevel;
        }

        //set levels
        switch ($logLevel) {
            case 'INFO':
                return Logger::INFO;
            case 'ERROR':
                return Logger::ERROR;
            case 'DEBUG':
            default:
                return Logger::DEBUG;
        }
    }
}
