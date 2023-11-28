<?php declare(strict_types=1);

namespace MoptWorldline\Service;

use MoptWorldline\Bootstrap\Form;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;

class OrderHelper
{
    /**
     * @param Context $context
     * @param EntityRepository $orderRepository
     * @param string $hostedCheckoutId
     * @return OrderEntity|null
     */
    public static function getOrder(
        Context                   $context,
        EntityRepository $orderRepository,
        string                    $hostedCheckoutId
    ): ?OrderEntity
    {
        $criteria = new Criteria();
        $criteria->addAssociation('transactions');
        $criteria->addFilter(
            new MultiFilter(
                MultiFilter::CONNECTION_AND,
                [
                    new EqualsFilter(
                        \sprintf('customFields.%s', Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID),
                        $hostedCheckoutId
                    ),
                    new NotFilter(
                        NotFilter::CONNECTION_AND,
                        [
                            new EqualsFilter(
                                \sprintf('customFields.%s', Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID),
                                null
                            ),
                        ]
                    ),
                ]
            )
        );

        /** @var OrderEntity|null $order */
        $order = $orderRepository->search($criteria, $context)->getEntities()->first();

        if ($order === null) {
            throw new InvalidTransactionException('');
        }

        return $order;
    }
}