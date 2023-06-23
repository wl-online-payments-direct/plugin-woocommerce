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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
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
    private EntityRepositoryInterface $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;

    /** @var array */
    private $credentialKeys = [
        'sandbox' => [
            'merchantId' => Form::MERCHANT_ID_FIELD,
            'apiSecret' => Form::API_SECRET_FIELD,
            'apiKey' => Form::API_KEY_FIELD,
            'endpoint' => Form::SANDBOX_ENDPOINT_FIELD
        ],
        'live' => [
            'merchantId' => Form::LIVE_MERCHANT_ID_FIELD,
            'apiSecret' => Form::LIVE_API_SECRET_FIELD,
            'apiKey' => Form::LIVE_API_KEY_FIELD,
            'endpoint' => Form::LIVE_ENDPOINT_FIELD
        ]
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
     * @param EntityRepositoryInterface $mediaRepository
     * @param MediaService $mediaService
     * @param FileSaver $fileSaver
     */
    public function __construct(
        SystemConfigService       $systemConfigService,
        EntityRepositoryInterface $salesChannelRepository,
        EntityRepositoryInterface $countryRepository,
        EntityRepositoryInterface $currencyRepository,
        Logger                    $logger,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $salesChannelPaymentRepository,
        PluginIdProvider          $pluginIdProvider,
        EntityRepositoryInterface $mediaRepository,
        MediaService              $mediaService,
        FileSaver                 $fileSaver
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
        $configFormData = $request->request->get('сonfigData');

        if (is_null($configFormData)) {
            return $this->response(false, "There is no config data.");
        }

        $salesChannelId = $request->request->get('salesChannelId');
        $mode = $this->getMode($request);

        [$countryIso3, $currencyIsoCode] = Helper::getSalesChannelData($salesChannelId);

        $credentials = $this->buildCredentials($salesChannelId, $configFormData, $mode);

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
     * @param Request $request
     * @return bool
     */
    private function getMode(Request $request): bool
    {
        $button = $request->request->get('button');
        return ($button == Form::LIVE_API_TEST_BUTTON) ? 'live' : 'sandbox';
;
    }

    /**
     * @param string|null $salesChannelId
     * @param array $configData
     * @param string $mode
     * @return array
     */
    private function buildCredentials(?string $salesChannelId, array $configData, string $mode): array
    {
        $globalConfig = [];
        if (array_key_exists('null', $configData)) {
            $globalConfig = $configData['null'];
        }

        //For "All Sales Channels" data will be in "null" part of configData
        $salesChannelId = $salesChannelId ?? 'null';

        $credentials = [];
        if (array_key_exists($salesChannelId, $configData)) {
            $channelConfig = $configData[$salesChannelId];
            foreach ($this->credentialKeys[$mode] as $key => $formKey) {
                if (array_key_exists($formKey, $channelConfig) && !is_null($channelConfig[$formKey])) {
                    $credentials[$key] = $channelConfig[$formKey];
                } elseif (array_key_exists($formKey, $globalConfig) && !is_null($globalConfig[$formKey])) {
                    $credentials[$key] = $globalConfig[$formKey];
                }
            }
        }

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
