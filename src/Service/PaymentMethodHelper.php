<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use MoptWorldline\Bootstrap\Form;
use MoptWorldline\MoptWorldline;
use Shopware\Core\Checkout\Payment\PaymentMethodEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Kernel;

class PaymentMethodHelper
{
    /**
     * @param EntityRepository $paymentRepository
     * @param PluginIdProvider $pluginIdProvider
     * @param Context $context
     * @param array $method
     * @param string|null $mediaId
     * @return string
     */
    public static function addPaymentMethod(
        EntityRepository $paymentRepository,
        PluginIdProvider $pluginIdProvider,
        Context          $context,
        array            $method,
        ?string          $mediaId = null
    ): string
    {
        $paymentMethodId = self::getPaymentMethodId($paymentRepository, (string)$method['id']);
        if ($paymentMethodId) {
            return $paymentMethodId;
        }
        $pluginId = $pluginIdProvider->getPluginIdByBaseClass(MoptWorldline::class, $context);

        $UUID = Uuid::randomHex();
        $paymentData = [
            'id' => $UUID,
            'handlerIdentifier' => Payment::class,
            'name' => $method['name'],
            'description' => $method['description'],
            'pluginId' => $pluginId,
            'afterOrderEnabled' => true,
            'active' => $method['active'],
            'customFields' => [
                Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID => $method['id']
            ],
            'mediaId' => $mediaId
        ];

        $paymentRepository->create([$paymentData], $context);

        return $UUID;
    }

    /**
     * @param string $paymentMethodId
     * @param string|null $salesChannelId
     * @param bool $isLinked
     * @param EntityRepository $salesChannelRepository
     * @param EntityRepository $salesChannelPaymentRepository
     * @param Context $context
     * @return void
     */
    public static function linkPaymentMethod(
        string           $paymentMethodId,
        ?string          $salesChannelId,
        bool             $isLinked,
        EntityRepository $salesChannelRepository,
        EntityRepository $salesChannelPaymentRepository,
        Context          $context,
    ): void
    {
        $toSave = [];
        if ($salesChannelId) {
            $toSave[] = [
                'salesChannelId' => $salesChannelId,
                'paymentMethodId' => $paymentMethodId
            ];
        } else {
            $salesChannelIds = $salesChannelRepository->searchIds(new Criteria(), $context)->getIds();
            foreach ($salesChannelIds as $salesChannelId) {
                $toSave[] = [
                    'salesChannelId' => $salesChannelId,
                    'paymentMethodId' => $paymentMethodId
                ];
            }
        }

        if ($isLinked) {
            $salesChannelPaymentRepository->create($toSave, $context);
        } else {
            $salesChannelPaymentRepository->delete($toSave, $context);
        }
    }

    /**
     * @param EntityRepository $paymentRepository
     * @param bool $active
     * @param Context $context
     * @param string $methodId
     * @return void
     */
    public static function setPaymentMethodStatus(
        EntityRepository $paymentRepository,
        bool             $active,
        Context          $context,
        string           $methodId
    )
    {
        $paymentMethodId = self::getPaymentMethodId($paymentRepository, $methodId);
        if (is_null($paymentMethodId)) {
            return;
        }

        self::setDBPaymentMethodStatus($paymentRepository, $active, $context, $paymentMethodId);
    }

    /**
     * @param EntityRepository $paymentRepository
     * @param bool $active
     * @param Context $context
     * @param string $paymentMethodId
     * @return void
     */
    public static function setDBPaymentMethodStatus(
        EntityRepository $paymentRepository,
        bool             $active,
        Context          $context,
        string           $paymentMethodId
    )
    {
        $paymentMethod = [
            'id' => $paymentMethodId,
            'active' => $active
        ];

        $paymentRepository->update([$paymentMethod], $context);
    }

    /**
     * @param EntityRepository $paymentRepository
     * @param string $worldlineMethodId
     * @return string|null
     */
    public static function getPaymentMethodId(EntityRepository $paymentRepository, string $worldlineMethodId): ?string
    {
        return $paymentRepository->searchIds(self::getCriteria($worldlineMethodId), Context::createDefaultContext())->firstId();
    }

    /**
     * @param string|null $salesChannelId
     * @return array
     * @throws \Doctrine\DBAL\Exception
     */
    public static function getPaymentMethods(?string $salesChannelId = ''): array
    {
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();

        $salesChannelCount = 0;
        if (empty($salesChannelId)) {
            $salesChannelCount = $connection->createQueryBuilder()
                ->select('COUNT(id)')
                ->from('sales_channel')
                ->executeQuery()
                ->fetchOne();
        }

        $key = Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID;
        $qb->select('
                HEX(pm.id) as internalId,
                pm.active, HEX(pm.media_id) as mediaId,
                pmt.name,
                HEX(scpm.sales_channel_id) as salesChannel,
                pmt.custom_fields as customFields
            ')
            ->from('payment_method', 'pm')
            ->leftJoin('pm', 'payment_method_translation', 'pmt', 'pm.id = pmt.payment_method_id')
            ->leftJoin('pm', 'sales_channel_payment_method', 'scpm', 'pm.id = scpm.payment_method_id')
            ->where("pmt.custom_fields LIKE '%$key%'");

        $dbMethods = self::buildMethods($qb->executeQuery()->fetchAllAssociative());

        self::setIsLinked($dbMethods, $salesChannelCount, $salesChannelId);

        return $dbMethods;
    }

    /**
     * @param string $worldlineMethodId
     * @return Criteria
     */
    private static function getCriteria(string $worldlineMethodId): Criteria
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('handlerIdentifier', Payment::class))
            ->addFilter(
                new MultiFilter(
                    MultiFilter::CONNECTION_AND,
                    [
                        new EqualsFilter(
                            \sprintf('customFields.%s', Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID),
                            $worldlineMethodId
                        ),
                        new NotFilter(
                            NotFilter::CONNECTION_AND,
                            [
                                new EqualsFilter(
                                    \sprintf('customFields.%s', Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID),
                                    null
                                ),
                            ]
                        ),
                    ]
                )
            );
        return $criteria;
    }


    /**
     * @param array $methods
     * @return array
     */
    protected static function buildMethods(array $methods): array
    {
        $dbMethods = [];
        foreach ($methods as $method) {
            $methodId = self::extractPaymentMethodId($method['customFields']);
            if (array_key_exists($methodId, $dbMethods)) {
                $dbMethods[$methodId]['salesChannels'][] = strtolower($method['salesChannel']);
            } else {
                $dbMethods[$methodId] = self::buildMethod($method);
            }
        }
        return $dbMethods;
    }

    /**
     * @param array $method
     * @return array
     */
    private static function buildMethod(array $method): array
    {
        return [
            'label' => $method['name'],
            'internalId' => strtolower((string)$method['internalId']),
            'isActive' => (bool)$method['active'],
            'mediaId' => strtolower((string)$method['mediaId']),
            'salesChannels' => is_null($method['salesChannel']) ? [] : [strtolower($method['salesChannel'])],
            'isLinked' => false
        ];
    }

    /**
     * @param string $str
     * @return string
     */
    private static function extractPaymentMethodId(string $str): string
    {
        $decoded = json_decode($str, true);
        $key = Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID;
        if (array_key_exists($key, $decoded)) {
            return (string)$decoded[$key];
        }
        return '';
    }

    /**
     * @param array $dbMethods
     * @param mixed $salesChannelCount
     * @param string|null $salesChannelId
     * @return void
     */
    protected static function setIsLinked(array &$dbMethods, mixed $salesChannelCount, ?string $salesChannelId): void
    {
        foreach ($dbMethods as $key => $method) {
            if ($salesChannelCount != 0 and count($method['salesChannels']) == $salesChannelCount) {
                $dbMethods[$key]['isLinked'] = true;
                continue;
            }

            if (in_array($salesChannelId, $method['salesChannels'])) {
                $dbMethods[$key]['isLinked'] = true;
            }
        }
    }

    /**
     * @param EntityRepository $paymentMethodRepository
     * @param EntityRepository $ruleRepository
     * @param EntityRepository $ruleConditionRepository
     * @param string $internalMethodId
     * @param string $methodId
     * @return void
     */
    public static function applyRuleToMethod(
        EntityRepository $paymentMethodRepository,
        EntityRepository $ruleRepository,
        EntityRepository $ruleConditionRepository,
        string $internalMethodId,
        string $methodId
    ): void
    {
        $ruleHelper = new RuleHelper($ruleRepository,$paymentMethodRepository, $ruleConditionRepository);
        if (array_key_exists($methodId, PaymentProducts::PAYMENT_PRODUCT_RULES)) {
            $ruleHelper->applyRule($methodId, $internalMethodId);
        }
    }
}
