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
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
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
        $mediaHelper = new MediaHelper(
            $this->mediaRepository, $this->mediaService, $this->fileSaver, $this->logger, $this->paymentMethodRepository
        );
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
                    $mediaHelper->createProductLogo($product, $context)
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
        $mediaHelper = new MediaHelper(
            $this->mediaRepository, $this->mediaService, $this->fileSaver, $this->logger, $this->paymentMethodRepository
        );

        $toFrontend = [];
        foreach (Payment::METHODS_LIST as $method) {
            $dbMethod = PaymentMethodHelper::getPaymentMethod($this->paymentMethodRepository, (string)$method['id']);
            $logo = $mediaHelper->getSystemMethodLogo($dbMethod, $method, $context);
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
            $createdPaymentMethod = PaymentMethodHelper::getPaymentMethod($this->paymentMethodRepository, (string)$product->getId());
            $logo = $mediaHelper->getPaymentMethodLogo($createdPaymentMethod, $product, $context);
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
}
