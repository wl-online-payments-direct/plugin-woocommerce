<?php

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\PaymentMethod;

use Monolog\Logger;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Bootstrap\Form;
use MoptWorldline\MoptWorldline;
use MoptWorldline\Service\Payment;
use OnlinePayments\Sdk\Domain\GetPaymentProductsResponse;
use OnlinePayments\Sdk\Domain\PaymentProduct;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelEntity;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class PaymentMethodController
{
    private SystemConfigService $systemConfigService;
    private Logger $logger;
    private EntityRepositoryInterface $paymentMethodRepository;
    private EntityRepositoryInterface $salesChannelPaymentRepository;
    private PluginIdProvider $pluginIdProvider;

    /**
     * @param SystemConfigService $systemConfigService
     * @param Logger $logger
     * @param EntityRepositoryInterface $paymentMethodRepository
     * @param EntityRepositoryInterface $salesChannelPaymentRepository
     * @param PluginIdProvider $pluginIdProvider
     */
    public function __construct(
        SystemConfigService       $systemConfigService,
        Logger                    $logger,
        EntityRepositoryInterface $paymentMethodRepository,
        EntityRepositoryInterface $salesChannelPaymentRepository,
        PluginIdProvider          $pluginIdProvider
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->logger = $logger;
        $this->paymentMethodRepository = $paymentMethodRepository;
        $this->salesChannelPaymentRepository = $salesChannelPaymentRepository;
        $this->pluginIdProvider = $pluginIdProvider;
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
                $this->processPaymentMethod($paymentMethod);
                continue;
            }

            if ($paymentMethod['status']) {
                $toCreate[] = $paymentMethod['id'];
            }
        }

        if (empty($toCreate)) {
            return $this->response(true, '', []);
        }
        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $adapter->getMerchantClient();
        $paymentMethods = $adapter->getPaymentMethods($countryIso3, $currencyIsoCode);
        foreach ($paymentMethods->getPaymentProducts() as $method) {
            if (in_array($method->getId(), $toCreate)) {
                $this->createPaymentMethod($method, $context, $salesChannelId);
            }
        }

        return $this->response(true, '', []);
    }

    /**
     * @param array $credentials
     * @param ?string $salesChannelId
     * @param ?string $countryIso3
     * @param ?string $currencyIsoCode
     * @return array
     * @throws \Exception
     */
    public function getPaymentMentodsList(
        array $credentials,
        ?string $salesChannelId,
        ?string $countryIso3,
        ?string $currencyIsoCode
    )
    {
        $fullRedirectMethod = $this->getPaymentMethod('Worldline');
        $toFrontend[] = [
            'id' => 0,
            'logo' => '',
            'label' => 'Worldline full redirect',
            'isActive' => $fullRedirectMethod['isActive'],
            'internalId' => $fullRedirectMethod['internalId']
        ];

        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $adapter->getMerchantClient($credentials);

        if (is_null($salesChannelId)) {
            $adapter->testConnection();
            return $toFrontend;
        }

        $paymentMethods = $adapter->getPaymentMethods($countryIso3, $currencyIsoCode);

        foreach ($paymentMethods->getPaymentProducts() as $method) {
            $name = $this->getPaymentMethodName($method->getDisplayHints()->getLabel());
            $createdPaymentMethod = $this->getPaymentMethod($name);

            $toFrontend[] = [
                'id' => $method->getId(),
                'logo' => $method->getDisplayHints()->getLogo(),
                'label' => $method->getDisplayHints()->getLabel(),
                'isActive' => $createdPaymentMethod['isActive'],
                'internalId' => $createdPaymentMethod['internalId']
            ];
        };
        return $toFrontend;
    }

    /**
     * @param PaymentProduct $method
     * @param Context $context
     * @param string $salesChannelId
     * @return void
     */
    private function createPaymentMethod(PaymentProduct $method, Context $context, string $salesChannelId)
    {
        $pluginId = $this->pluginIdProvider->getPluginIdByBaseClass(MoptWorldline::class, $context);

        $name = $this->getPaymentMethodName($method->getDisplayHints()->getLabel());
        $paymentMethodId = Uuid::randomHex();
        $paymentData = [
            'id' => $paymentMethodId,
            'handlerIdentifier' => Payment::class,
            'name' => $name,
            'pluginId' => $pluginId,
            'active' => true,
            'afterOrderEnabled' => true,
            'customFields' => [
                Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID => $method->getId()
            ]
        ];

        $salesChannelPaymentData = [
            'salesChannelId' => $salesChannelId,
            'paymentMethodId' => $paymentMethodId
        ];

        $dbPaymentMethod =$this->getPaymentMethod($name);

        if (empty($dbPaymentMethod['internalId'])) {
            $this->paymentMethodRepository->create([$paymentData], $context);
            $this->salesChannelPaymentRepository->create([$salesChannelPaymentData], $context);
        }
    }

    /**
     * @param $paymentMethod
     * @return void
     */
    private function processPaymentMethod($paymentMethod)
    {
        $data = [
            'id' => $paymentMethod['internalId'],
            'active' => $paymentMethod['status']
        ];
        $this->paymentMethodRepository->update([$data], Context::createDefaultContext());
    }

    /**
     * @param $name
     * @return array
     */
    private function getPaymentMethod($name): array
    {
        $paymentCriteria = (
        new Criteria())
            ->addFilter(new EqualsFilter('handlerIdentifier', Payment::class))
            ->addFilter(new EqualsFilter('name', $name));
        $payments = $this->paymentMethodRepository->search($paymentCriteria, Context::createDefaultContext());

        /** @var PaymentMethodEntity $payment */
        foreach ($payments as $payment) {
            return [
                'internalId' => $payment->getId(),
                'isActive' => $payment->getActive()
            ];
        }

        return [
            'internalId' => '',
            'isActive' => false
        ];
    }

    /**
     * @param $label
     * @return string
     */
    private function getPaymentMethodName($label)
    {
        return "Worldline $label";
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
