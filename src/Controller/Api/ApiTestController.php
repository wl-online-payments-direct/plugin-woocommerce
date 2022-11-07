<?php

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\Api;

use Monolog\Logger;
use MoptWorldline\Bootstrap\Form;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\System\Country\CountryEntity;
use Shopware\Core\System\Currency\CurrencyEntity;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptWorldline\Controller\PaymentMethod\PaymentMethodController;

/**
 * @RouteScope(scopes={"api"})
 */
class ApiTestController extends AbstractController
{
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $salesChannelRepository;
    private EntityRepositoryInterface $countryRepository;
    private EntityRepositoryInterface $currencyRepository;
    private Logger $logger;
    private EntityRepositoryInterface $paymentMethodRepository;
    private EntityRepositoryInterface $salesChannelPaymentRepository;
    private PluginIdProvider $pluginIdProvider;

    /** @var array */
    private $credentialKeys = [
        'merchantId' => Form::MERCHANT_ID_FIELD,
        'apiSecret' => Form::API_SECRET_FIELD,
        'apiKey' => Form::API_KEY_FIELD,
        'isLiveMode' => Form::IS_LIVE_MODE_FIELD
    ];

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $salesChannelRepository
     * @param EntityRepositoryInterface $countryRepository
     * @param EntityRepositoryInterface $currencyRepository
     * @param Logger $logger
     * @param EntityRepositoryInterface $paymentMethodRepository
     * @param EntityRepositoryInterface $salesChannelPaymentRepository
     * @param PluginIdProvider $pluginIdProvider
     */
    public function __construct(
        SystemConfigService       $systemConfigService,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $currencyRepository,
        Logger                    $logger,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $salesChannelPaymentRepository,
        PluginIdProvider          $pluginIdProvider
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->countryRepository = $countryRepository;
        $this->currencyRepository = $currencyRepository;
        $this->logger = $logger;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelPaymentRepository = $salesChannelPaymentRepository;
        $this->pluginIdProvider = $pluginIdProvider;
    }

    /**
     * @Route(
     *     "/api/_action/api-test/test-connection",
     *     name="api.action.test.connection",
     *     methods={"POST"}
     * )
     */
    public function testConnection(Request $request, Context $context): JsonResponse
    {
        $configFormData = $request->request->get('сonfigData');

        if (is_null($configFormData)) {
            return $this->response(false, "There is no config data.");
        }

        $salesChannelId = $request->request->get('salesChannelId');

        [$countryIso3, $currencyIsoCode] = $this->getSalesChannelData($salesChannelId, $context);

        $credentials = $this->buildCredentials($salesChannelId, $configFormData);

        $paymentMethods = [];
        $message = '';
        try {
            $paymentMethodController = $this->getPaymentMethodController();
            $paymentMethods = $paymentMethodController->getPaymentMentodsList(
                $credentials,
                $salesChannelId,
                $countryIso3,
                $currencyIsoCode
            );
        } catch (\Exception $e) {
            $message = '<br/>' . $e->getMessage();
        }

        $success = empty($message);

        return $this->response($success, $message, $paymentMethods);
    }

    /**
     * @Route(
     *     "/api/_action/api-test/savemethod",
     *     name="api.action.test.savemethod",
     *     methods={"POST"}
     * )
     */
    public function saveMethod(Request $request, Context $context): JsonResponse
    {
        $paymentMethodController = $this->getPaymentMethodController();
        $salesChannelId = $request->request->get('salesChannelId');
        [$countryIso3, $currencyIsoCode] = $this->getSalesChannelData($salesChannelId, $context);

        return $paymentMethodController->saveMethod($request, $context, $countryIso3, $currencyIsoCode);
    }

    /**
     * @param ?string $salesChannelId
     * @param Context $context
     * @return array
     */
    private function getSalesChannelData(?string $salesChannelId, Context $context): array
    {
        if (is_null($salesChannelId)) {
            return [null, null];
        }

        /* @var $salesChannel SalesChannelEntity */
        $salesChannel = $this->salesChannelRepository->search(new Criteria([$salesChannelId]), $context)->first();
        /* @var $country CountryEntity */
        $country = $this->countryRepository->search(new Criteria([$salesChannel->getCountryId()]), $context)->first();
        /* @var $currency CurrencyEntity */
        $currency = $this->currencyRepository->search(new Criteria([$salesChannel->getCurrencyId()]), $context)->first();

        return [$country->getIso3(), $currency->getIsoCode()];
    }

    /**
     * @return PaymentMethodController
     */
    private function getPaymentMethodController()
    {
        return new PaymentMethodController(
            $this->systemConfigService,
            $this->logger,
            $this->paymentMethodRepository,
            $this->salesChannelPaymentRepository,
            $this->pluginIdProvider
        );
    }

    /**
     * @param ?string $salesChannelId
     * @param array $configData
     * @return array
     */
    private function buildCredentials(?string $salesChannelId, array $configData): array
    {
        $globalConfig = [];
        if (array_key_exists('null', $configData)) {
            $globalConfig = $configData['null'];
        }

        $credentials = [
            'isLiveMode' => false
        ];

        //For "All Sales Channels" data will be in "null" part of configData
        $salesChannelId = $salesChannelId ?? 'null';

        if (array_key_exists($salesChannelId, $configData)) {
            $channelConfig = $configData[$salesChannelId];
            foreach ($this->credentialKeys as $key => $formKey) {
                if (array_key_exists($formKey, $channelConfig) && !is_null($channelConfig[$formKey])) {
                    $credentials[$key] = $channelConfig[$formKey];
                } elseif (array_key_exists($formKey, $globalConfig) && !is_null($globalConfig[$formKey])) {
                    $credentials[$key] = $globalConfig[$formKey];
                }
            }
        }

        $credentials['isLiveMode'] = (bool)$credentials['isLiveMode'];

        return $credentials;
    }

    /**
     * @param bool $success
     * @param string $message
     * @return JsonResponse
     */
    private function response(bool $success, string $message, $paymentMethods = []): JsonResponse
    {
        return new JsonResponse([
            'success' => $success,
            'message' => $message,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}
