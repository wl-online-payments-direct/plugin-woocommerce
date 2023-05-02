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
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\Plugin\Util\PluginIdProvider;
use Shopware\Core\Framework\Uuid\Uuid;

class PaymentMethodHelper
{
    /**
     * @param EntityRepositoryInterface $paymentRepository
     * @param EntityRepositoryInterface $salesChannelPaymentRepository
     * @param PluginIdProvider $pluginIdProvider
     * @param Context $context
     * @param array $method
     * @param string|null $salesChannelId
     * @param EntityRepositoryInterface|null $salesChannelRepository
     * @param string|null $mediaId
     * @return void
     */
    public static function addPaymentMethod(
        EntityRepositoryInterface  $paymentRepository,
        EntityRepositoryInterface  $salesChannelPaymentRepository,
        PluginIdProvider           $pluginIdProvider,
        Context                    $context,
        array                      $method,
        ?string                    $salesChannelId,
        ?EntityRepositoryInterface $salesChannelRepository,
        ?string                    $mediaId = null
    )
    {
        $paymentMethodExists = self::getPaymentMethodId($paymentRepository, (string)$method['id']);
        if ($paymentMethodExists) {
            return;
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

        $toSave = [];
        if ($salesChannelId) {
            $toSave[] = [
                'salesChannelId' => $salesChannelId,
                'paymentMethodId' => $UUID
            ];
        } else {
            $salesChannelIds = $salesChannelRepository->searchIds(new Criteria(), $context)->getIds();
            foreach ($salesChannelIds as $salesChannelId) {
                $toSave[] = [
                    'salesChannelId' => $salesChannelId,
                    'paymentMethodId' => $UUID
                ];
            }
        }

        $salesChannelPaymentRepository->create($toSave, $context);
    }

    /**
     * @param EntityRepositoryInterface $paymentRepository
     * @param bool $active
     * @param Context $context
     * @param string $methodId
     * @return void
     */
    public static function setPaymentMethodStatus(
        EntityRepositoryInterface $paymentRepository,
        bool                      $active,
        Context                   $context,
        string                    $methodId
    )
    {
        $paymentMethodId = self::getPaymentMethodId($paymentRepository, $methodId);
        if (is_null($paymentMethodId)) {
            return;
        }

        self::setDBPaymentMethodStatus($paymentRepository, $active, $context, $paymentMethodId);
    }

    /**
     * @param EntityRepositoryInterface $paymentRepository
     * @param bool $active
     * @param Context $context
     * @param string $paymentMethodId
     * @return void
     */
    public static function setDBPaymentMethodStatus(
        EntityRepositoryInterface $paymentRepository,
        bool                      $active,
        Context                   $context,
        string                    $paymentMethodId
    )
    {
        $paymentMethod = [
            'id' => $paymentMethodId,
            'active' => $active
        ];

        $paymentRepository->update([$paymentMethod], $context);
    }

    /**
     * @param EntityRepositoryInterface $paymentRepository
     * @param string $worldlineMethodId
     * @return string|null
     */
    public static function getPaymentMethodId(EntityRepositoryInterface $paymentRepository, string $worldlineMethodId): ?string
    {
        return $paymentRepository->searchIds(self::getCriteria($worldlineMethodId), Context::createDefaultContext())->firstId();
    }

    /**
     * @param EntityRepositoryInterface $paymentRepository
     * @param string $worldlineMethodId
     * @return array
     */
    public static function getPaymentMethod(EntityRepositoryInterface $paymentRepository, string $worldlineMethodId): array
    {
        /** @var PaymentMethodEntity $method */
        $method = $paymentRepository->search(self::getCriteria($worldlineMethodId), Context::createDefaultContext())->first();

        if ($method) {
            return [
                'label' => $method->getName(),
                'internalId' => $method->getId(),
                'isActive' => $method->getActive(),
                'mediaId' => $method->getMediaId()
            ];
        }

        return [
            'label' => '',
            'internalId' => '',
            'isActive' => false,
            'mediaId' => null
        ];
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
}
