<?php

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\Api;

use Monolog\Logger;
use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\Helper;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaService;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptWorldline\Controller\PaymentMethod\PaymentMethodController;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class ApiTestController extends AbstractController
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $salesChannelRepository;
    private EntityRepository $countryRepository;
    private EntityRepository $currencyRepository;
    private Logger $logger;
    private EntityRepository $paymentMethodRepository;
    private EntityRepository $salesChannelPaymentRepository;
    private PluginIdProvider $pluginIdProvider;
    private EntityRepository $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;

    /** @var array */
    private $credentialKeys = [
        'merchantId' => Form::MERCHANT_ID_FIELD,
        'apiSecret' => Form::API_SECRET_FIELD,
        'apiKey' => Form::API_KEY_FIELD,
        'isLiveMode' => Form::IS_LIVE_MODE_FIELD
    ];

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $salesChannelRepository
     * @param EntityRepository $countryRepository
     * @param EntityRepository $currencyRepository
     * @param Logger $logger
     * @param EntityRepository $paymentMethodRepository
     * @param EntityRepository $salesChannelPaymentRepository
     * @param PluginIdProvider $pluginIdProvider
     * @param EntityRepository $mediaRepository
     * @param MediaService $mediaService
     * @param FileSaver $fileSaver
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository    $salesChannelRepository,
        EntityRepository    $countryRepository,
        EntityRepository    $currencyRepository,
        Logger              $logger,
        EntityRepository    $paymentMethodRepository,
        EntityRepository    $salesChannelPaymentRepository,
        PluginIdProvider    $pluginIdProvider,
        EntityRepository    $mediaRepository,
        MediaService        $mediaService,
        FileSaver           $fileSaver
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
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
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
        $configFormData = $request->request->all('сonfigData');

        if (is_null($configFormData)) {
            return $this->response(false, "There is no config data.");
        }

        $salesChannelId = $request->request->get('salesChannelId');

        [$countryIso3, $currencyIsoCode] = Helper::getSalesChannelData($salesChannelId);

        $credentials = $this->buildCredentials($salesChannelId, $configFormData);

        $paymentMethods = [];
        $message = '';
        try {
            $paymentMethodController = $this->getPaymentMethodController();
            $paymentMethods = $paymentMethodController->getPaymentMethodsList(
                $credentials,
                $salesChannelId,
                $countryIso3,
                $currencyIsoCode,
                $context
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
        [$countryIso3, $currencyIsoCode] = Helper::getSalesChannelData($salesChannelId);

        return $paymentMethodController->saveMethod($request, $context, $countryIso3, $currencyIsoCode);
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
            $this->pluginIdProvider,
            $this->mediaRepository,
            $this->mediaService,
            $this->fileSaver
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
