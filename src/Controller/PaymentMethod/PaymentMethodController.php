<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\PaymentMethod;

use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Service\MediaHelper;
use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentMethodHelper;
use MoptWorldline\Service\PaymentProducts;
use OnlinePayments\Sdk\Domain\PaymentProduct;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Core\Content\Media\File\FileSaver;
use Shopware\Core\Content\Media\MediaService;

class PaymentMethodController
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $paymentMethodRepository;
    private EntityRepository $salesChannelPaymentRepository;
    private PluginIdProvider $pluginIdProvider;
    private EntityRepository $mediaRepository;
    private MediaService $mediaService;
    private FileSaver $fileSaver;
    private EntityRepository $salesChannelRepository;
    private EntityRepository $ruleRepository;
    private EntityRepository $ruleConditionRepository;

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $paymentMethodRepository
     * @param EntityRepository $salesChannelPaymentRepository
     * @param PluginIdProvider $pluginIdProvider
     * @param EntityRepository $mediaRepository
     * @param MediaService $mediaService
     * @param FileSaver $fileSaver
     * @param EntityRepository $salesChannelRepository
     * @param EntityRepository $ruleRepository
     * @param EntityRepository $ruleConditionRepository
     */
    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepository    $paymentMethodRepository,
        EntityRepository    $salesChannelPaymentRepository,
        PluginIdProvider    $pluginIdProvider,
        EntityRepository    $mediaRepository,
        MediaService        $mediaService,
        FileSaver           $fileSaver,
        EntityRepository    $salesChannelRepository,
        EntityRepository    $ruleRepository,
        EntityRepository    $ruleConditionRepository,
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelPaymentRepository = $salesChannelPaymentRepository;
        $this->pluginIdProvider = $pluginIdProvider;
        $this->mediaRepository = $mediaRepository;
        $this->mediaService = $mediaService;
        $this->fileSaver = $fileSaver;
        $this->salesChannelRepository = $salesChannelRepository;
        $this->ruleRepository = $ruleRepository;
        $this->ruleConditionRepository = $ruleConditionRepository;
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
        $data = $request->request->all('data');
        $salesChannelId = (string)$request->request->get('salesChannelId');

        $toCreate = [];
        $toLink = [];
        $toStatusChange = [];
        $toApplyRule = [];

        foreach ($data as $method) {
            if (empty($method['internalId']) && ($method['status'] || $method['isLinked'])) {
                $toCreate[$method['id']] = [
                    'id' => $method['id'],
                    'status' => $method['status'],
                    'isLinked' => $method['isLinked']
                ];
                continue;
            }
            if (!empty($method['internalId'])) {
                $toLink[$method['internalId']] = $method['isLinked'];
                $toStatusChange[$method['internalId']] = $method['status'];
                $toApplyRule[$method['internalId']] = (string)$method['id'];
            }
        }

        $this->createMethods($toCreate, $salesChannelId, $countryIso3, $currencyIsoCode, $context);
        $this->linkMethods($toLink, $salesChannelId, $context);
        $this->changeStatus($toStatusChange, $context);
        $this->applyRule($toApplyRule);

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
            $this->mediaRepository, $this->mediaService, $this->fileSaver, $this->paymentMethodRepository
        );

        $dbMethods = PaymentMethodHelper::getPaymentMethods($salesChannelId);

        $toFrontend = [];
        foreach (Payment::METHODS_LIST as $method) {
            if (array_key_exists($method['id'], $dbMethods)) {
                $dbMethod = $dbMethods[$method['id']];
            } else {
                continue;
            }

            $logo = $method['logo'] ? $mediaHelper->getSystemMethodLogo($dbMethod, $method, $context) : '';
            $toFrontend[] = [
                'id' => $method['id'],
                'logo' => $logo,
                'label' => $dbMethod['label'],
                'isActive' => $dbMethod['isActive'],
                'internalId' => $dbMethod['internalId'],
                'isLinked' => $dbMethod['isLinked']
            ];
        }

        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $salesChannelId);
        $adapter->getMerchantClient($credentials);

        $paymentProducts = $adapter->getPaymentProducts($countryIso3, $currencyIsoCode);
        $methods = $paymentProducts->getPaymentProducts();
        foreach ($methods as $product) {
            $key = $product->getId();
            if (array_key_exists($key, $dbMethods)) {
                $dbMethod = $dbMethods[$key];
            } else {
                $dbMethod = [
                    'label' => '',
                    'internalId' => '',
                    'isActive' => false,
                    'mediaId' => null,
                    'isLinked' => false
                ];
            }
            $logo = $mediaHelper->getPaymentMethodLogo($dbMethod, $product, $context);
            $toFrontend[] = [
                'id' => $product->getId(),
                'logo' => $logo,
                'label' => $dbMethod['label'] ?: $product->getDisplayHints()->getLabel(),
                'isActive' => $dbMethod['isActive'],
                'internalId' => $dbMethod['internalId'],
                'isLinked' => $dbMethod['isLinked']
            ];
        }

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
     * @param array $methods
     * @param string $salesChannelId
     * @param string|null $countryIso3
     * @param string|null $currencyIsoCode
     * @param Context $context
     * @return void
     * @throws \Exception
     */
    private function createMethods(array $methods, string $salesChannelId, ?string $countryIso3, ?string $currencyIsoCode, Context $context): void
    {
        if (empty($methods)) {
            return;
        }
        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $salesChannelId);
        $mediaHelper = new MediaHelper(
            $this->mediaRepository, $this->mediaService, $this->fileSaver, $this->paymentMethodRepository
        );
        $adapter->getMerchantClient();
        $paymentProducts = $adapter->getPaymentProducts($countryIso3, $currencyIsoCode);

        foreach ($paymentProducts->getPaymentProducts() as $product) {
            $name = $this->getProductName($product);

            if (array_key_exists($product->getId(), $methods)) {
                $method = [
                    'id' => $product->getId(),
                    'name' => $name,
                    'description' => '',
                    'active' => $methods[$product->getId()]['status'],
                ];

                $newMethodId = PaymentMethodHelper::addPaymentMethod(
                     $this->paymentMethodRepository,
                     $this->pluginIdProvider,
                     $context,
                     $method,
                     $mediaHelper->createProductLogo($product, $context)
                 );

                PaymentMethodHelper::linkPaymentMethod(
                    $newMethodId,
                    null,
                    true,
                    $this->salesChannelRepository,
                    $this->salesChannelPaymentRepository,
                    $context
                );

                PaymentMethodHelper::applyRuleToMethod(
                    $this->paymentMethodRepository,
                    $this->ruleRepository,
                    $this->ruleConditionRepository,
                    $newMethodId,
                    (string)$product->getId()
                );
            }
        }
    }

    /**
     * @param array $methods
     * @param Context $context
     * @return void
     */
    private function changeStatus(array $methods, Context $context): void
    {
        foreach ($methods as $id => $status) {
            PaymentMethodHelper::setDBPaymentMethodStatus(
                $this->paymentMethodRepository,
                $status,
                $context,
                $id
            );
        }
    }

    /**
     * @param array $methods
     * @param string|null $salesChannel
     * @param Context $context
     * @return void
     */
    private function linkMethods(array $methods, ?string $salesChannel, Context $context): void
    {
        if (empty($methods)) {
            return;
        }
        foreach ($methods as $id => $isLinked) {
            PaymentMethodHelper::linkPaymentMethod(
                $id,
                $salesChannel,
                $isLinked,
                $this->salesChannelRepository,
                $this->salesChannelPaymentRepository,
                $context
            );
        }
    }

    /**
     * @param array $methods
     * @return void
     */
    private function applyRule(array $methods): void
    {
        if (empty($methods)) {
            return;
        }
        foreach ($methods as $internalMethodId => $methodId) {
            PaymentMethodHelper::applyRuleToMethod(
                $this->paymentMethodRepository,
                $this->ruleRepository,
                $this->ruleConditionRepository,
                $internalMethodId,
                $methodId
            );
        }
    }
}
