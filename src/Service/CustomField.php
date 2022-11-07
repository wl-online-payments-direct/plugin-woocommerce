<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\CustomField\CustomFieldTypes;
use Symfony\Component\DependencyInjection\ContainerInterface;
use MoptWorldline\Bootstrap\Form;

class CustomField
{
    private ContainerInterface $container;

    /**
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param InstallContext $installContext
     * @return void
     */
    public function addCustomFields(InstallContext $installContext)
    {
        $fieldIds = $this->customFieldsExist($installContext->getContext());

        if ($fieldIds) {
            return;
        }

        $customFieldSetRepository = $this->container->get('custom_field_set.repository');
        $customFieldSetRepository->upsert([
            $this->getOrderTransactionFieldset(),
        ], $installContext->getContext());
    }

    /**
     * @param Context $context
     * @return mixed
     */
    public function customFieldsExist(Context $context)
    {
        $customFieldSetRepository = $this->container->get('custom_field_set.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter(
            'name',
            [
                Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_FIELDSET,
            ]
        ));

        $ids = $customFieldSetRepository->searchIds($criteria, $context);

        return $ids->getTotal() > 0 ? $ids : null;
    }

    /**
     * @return array
     */
    private function getOrderTransactionFieldset(): array
    {
        return [
            'id' => Uuid::randomHex(),
            'name' => Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_FIELDSET,
            'config' => [
                'label' => [
                    'de-DE' => 'Worldline Zahlungstransaktions',
                    'en-GB' => 'Worldline payment transaction'
                ]
            ],
            'customFields' => [
                [
                    'id' => Uuid::randomHex(),
                    'name' => Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'de-DE' => 'ID',
                            'en-GB' => 'ID'
                        ]
                   ]
                ],
                [
                    'id' => Uuid::randomHex(),
                    'name' => Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_READABLE_STATUS,
                    'type' => CustomFieldTypes::TEXT,
                    'config' => [
                        'label' => [
                            'de-DE' => 'Status',
                            'en-GB' => 'Status'
                        ]
                    ]
                ]
            ],
            'relations' => [
                [
                    'id' => Uuid::randomHex(),
                    'entityName' => 'order'
                ]
            ]
        ];
    }
}
