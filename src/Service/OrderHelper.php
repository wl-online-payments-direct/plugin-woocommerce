<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Monolog\Level;
use MoptWorldline\Bootstrap\Form;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Payment\Exception\InvalidTransactionException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\MultiFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Kernel;

class OrderHelper
{
    /**
     * @param Context $context
     * @param EntityRepository $orderRepository
     * @param string $hostedCheckoutId
     * @return OrderEntity|null
     */
    public static function getOrder(
        Context          $context,
        EntityRepository $orderRepository,
        string           $hostedCheckoutId
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
            LogHelper::addLog(Level::Error, 'The order with hostedCheckoutId $hostedCheckoutId could not be found.');
            throw new InvalidTransactionException('');
        }

        return $order;
    }

    /**
     * @param $customFields
     * @return bool
     */
    public static function isOrderLocked($customFields): bool
    {
        if (array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_IS_LOCKED, $customFields)) {
            return $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_IS_LOCKED];
        }
        return false;
    }

    /**
     * @param OrderEntity $orderEntity
     * @param LogHelper $logger
     * @return false|mixed
     */
    public static function getCurrencyISO(OrderEntity $orderEntity, LogHelper $logger)
    {
        $currencyId = $orderEntity->getCurrencyId();

        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('iso_code')
            ->from('currency', 'c')
            ->where("c.id = UNHEX('$currencyId')");

        try {
            $currencyISO = $qb->fetchAssociative();
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, $e->getMessage());
        }

        if (array_key_exists('iso_code', $currencyISO)) {
            return $currencyISO['iso_code'];
        }

        $logger->paymentLog($orderEntity->getOrderNumber(), 'cantFindCurrencyOfOrder' . $currencyId, Level::Error);
        return false;
    }
}