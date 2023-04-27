<?php

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\PaymentMethod;

use Monolog\Logger;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Service\MediaHelper;
use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentMethodHelper;
use MoptWorldline\Service\PaymentProducts;
use OnlinePayments\Sdk\Domain\PaymentProduct;
use Shopware\Core\Content\Media\MediaEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaService;

class PaymentMethodController
{
    private SystemConfigService $systemConfigService;
    private Logger $logger;
    private EntityRepositoryInterface $paymentMethodRepository;
    private EntityRepositoryInterface $salesChannelPaymentRepository;
    private PluginIdProvider $pluginIdProvider;
    private EntityRepositoryInterface $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;

    /**
     * @param SystemConfigService $systemConfigService
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
        $this->logger = $logger;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelPaymentRepository = $salesChannelPaymentRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
    }

    /**
     * @param Request $request
     * @param Context $context
     * @param ?string $countryIso3
     * @param ?string $currencyIsoCode
     * @return JsonResponse
     * @throws \Exception
     */
    public function saveMethod(Request $request, Context $context, ?string $countryIso3, ?string $currencyIsoCode): JsonResponse
    {
        $data = $request->request->get('data');
        $salesChannelId = $request->request->get('salesChannelId');

        $toCreate = [];
        foreach ($data as $paymentMethod) {
            if (!empty($paymentMethod['internalId'])) {
                //Activate/deactivate method, that already exist
                PaymentMethodHelper::setDBPaymentMethodStatus(
                    $this->paymentMethodRepository,
                    $paymentMethod['status'],
                    $context,
                    $paymentMethod['internalId']
                );
                continue;
            }

            if ($paymentMethod['status']) {
                $toCreate[] = $paymentMethod['id'];
            }
        }

        if (empty($toCreate)) {
            return $this->response();
        }
        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $mediaHelper = new MediaHelper($this->mediaRepository, $this->mediaService, $this->fileSaver, $this->logger);
        $adapter->getMerchantClient();
        $paymentProducts = $adapter->getPaymentProducts($countryIso3, $currencyIsoCode);
        foreach ($paymentProducts->getPaymentProducts() as $product) {
            $name = $this->getProductName($product);
            if (in_array($product->getId(), $toCreate)) {
                $method = [
                    'id' => $product->getId(),
                    'name' => $name,
                    'description' => '',
                    'active' => true,
                ];
                PaymentMethodHelper::addPaymentMethod(
                    $this->paymentMethodRepository,
                    $this->salesChannelPaymentRepository,
                    $this->pluginIdProvider,
                    $context,
                    $method,
                    $salesChannelId,
                    null,
                    $mediaHelper->createLogo($product, $context)
                );
            }
        }

        return $this->response();
    }

    /**
     * @param array $credentials
     * @param string|null $salesChannelId
     * @param string|null $countryIso3
     * @param string|null $currencyIsoCode
     * @param Context $context
     * @return array
     * @throws \Exception
     */
    public function getPaymentMethodsList(
        array   $credentials,
        ?string $salesChannelId,
        ?string $countryIso3,
        ?string $currencyIsoCode,
        Context $context
    ): array
    {
        $toFrontend = [];
        foreach (Payment::METHODS_LIST as $method) {
            $dbMethod = PaymentMethodHelper::getPaymentMethod($this->paymentMethodRepository, (string)$method['id'], $salesChannelId);
            $logo = $this->getLogo($dbMethod, $method, $context);
            $toFrontend[] = [
                'id' => $method['id'],
                'logo' => $logo,
                'label' => $dbMethod['label'],
                'isActive' => $dbMethod['isActive'],
                'internalId' => $dbMethod['internalId']
            ];
        }

        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $adapter->getMerchantClient($credentials);

        if (is_null($salesChannelId)) {
            $adapter->testConnection();
            return $toFrontend;
        }

        $paymentProducts = $adapter->getPaymentProducts($countryIso3, $currencyIsoCode);
        foreach ($paymentProducts->getPaymentProducts() as $product) {
            $createdPaymentMethod = PaymentMethodHelper::getPaymentMethod($this->paymentMethodRepository, (string)$product->getId(), $salesChannelId);
            $logo = $product->getDisplayHints()->getLogo();
            if (!is_null($createdPaymentMethod['mediaId'])) {
                $logo = $this->loadLogo($createdPaymentMethod['mediaId'], $context);
            }

            $toFrontend[] = [
                'id' => $product->getId(),
                'logo' => $logo,
                'label' => $createdPaymentMethod['label'] ?: $product->getDisplayHints()->getLabel(),
                'isActive' => $createdPaymentMethod['isActive'],
                'internalId' => $createdPaymentMethod['internalId']
            ];
        };
        return $toFrontend;
    }

    /**
     * @param bool $success
     * @param string $message
     * @param $paymentMethods
     * @return JsonResponse
     */
    private function response(bool $success = true, string $message = '', $paymentMethods = []): JsonResponse
    {
        return new JsonResponse([
            'success' => $success,
            'message' => $message,
            'paymentMethods' => $paymentMethods,
        ]);
    }

    /**
     * @param PaymentProduct $product
     * @return string
     */
    private function getProductName(PaymentProduct $product): string
    {
        $id = $product->getId();
        $name = 'Worldline ' . $product->getDisplayHints()->getLabel();
        if (array_key_exists($id, PaymentProducts::PAYMENT_PRODUCT_NAMES)) {
            $name = PaymentProducts::PAYMENT_PRODUCT_NAMES[$id];
        }

        return $name;
    }

    /**
     * @param array $dbMethod
     * @param array $method
     * @param Context $context
     * @return string
     */
    private function getLogo(array $dbMethod, array $method, Context $context): string
    {
        if (is_null($dbMethod['mediaId'])) {
            $mediaId = $this->createLogo($method['id'], $dbMethod['internalId'], $context);
        } else {
            $mediaId = $dbMethod['mediaId'];
        }

        return $this->loadLogo($mediaId, $context) ?: '';
    }


    /**
     * @param $mediaId
     * @param $context
     * @return string
     */
    private function loadLogo($mediaId, $context): string
    {
        $result = $this->mediaRepository->search(new Criteria([$mediaId]), $context);
        $url = '';
        /** @var MediaEntity $media */
        foreach ($result->getElements() as $media) {
            $url = $media->getUrl();
            break;
        }
        return $url;
    }

    /**
     * @param string $logoName
     * @param string $paymentMethodId
     * @param Context $context
     * @return ?string
     */
    private function createLogo(string $logoName, string $paymentMethodId, Context $context): ?string
    {
        $mediaHelper = new MediaHelper($this->mediaRepository, $this->mediaService, $this->fileSaver, $this->logger);
        $logoPath = \sprintf('%s/%s.png', PaymentProducts::PAYMENT_PRODUCT_MEDIA_DIR, $logoName);
        $mediaId = $mediaHelper->createMediaFromFile($logoPath, $logoName, 'png', $context);
        $paymentMethod = [
            'id' => $paymentMethodId,
            'mediaId' => $mediaId
        ];

        $this->paymentMethodRepository->update([$paymentMethod], $context);

        return $mediaId;
    }
}
