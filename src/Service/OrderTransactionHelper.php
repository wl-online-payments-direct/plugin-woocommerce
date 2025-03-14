<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Monolog\Level;
use MoptWorldline\Bootstrap\Form;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Kernel;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\StateMachine\Transition;

class OrderTransactionHelper
{
    /**
     * @param StateMachineRegistry $stateMachineRegistry
     * @param Context $context
     * @param string $orderTransactionId
     * @return void
     */
    public static function paidPartiallyToPaid(
        StateMachineRegistry $stateMachineRegistry,
        Context              $context,
        string               $orderTransactionId
    ): void
    {
        $stateMachineRegistry->transition(
            new Transition(
                OrderTransactionDefinition::ENTITY_NAME,
                $orderTransactionId,
                'pay',
                'stateId'
            ), $context
        );
    }

    /**
     * @param OrderTransactionEntity $transaction
     * @return mixed
     */
    public static function getWorldlinePaymentMethodId(OrderTransactionEntity $transaction): string
    {
        $customFields = $transaction->getPaymentMethod()->getCustomFields();

        if (!is_array($customFields)
            || !array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID, $customFields)
            || empty($customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID])
        ) {
            return self::getCustomFieldFromTransaction($transaction);
        }

        return (string)$customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID];
    }

    /**
     * @param string $orderTransactionId
     * @return string
     */
    public static function getState(string $orderTransactionId): string
    {
        $connection = Kernel::getConnection();
        $qb = $connection->createQueryBuilder();
        $qb->select('sms.technical_name as name')
            ->from('order_transaction', 'ot')
            ->leftJoin('ot', 'state_machine_state', 'sms', 'sms.id = ot.state_id')
            ->where("ot.id = UNHEX('$orderTransactionId')");

        try {
            $state = $qb->fetchAssociative();
            if (array_key_exists('name', $state)) {
                return $state['name'];
            }
        } catch (\Exception $e) {
            LogHelper::addLog(Level::Error, "Can't find state for transaction $orderTransactionId", $e->getMessage());
        }

        return '';
    }

    /**
     * #35519 Special case for other language subshop have no custom field
     * @param OrderTransactionEntity $transaction
     * @return mixed
     */
    private static function getCustomFieldFromTransaction(OrderTransactionEntity $transaction): mixed
    {
        $translated = $transaction->getPaymentMethod()->getTranslated();
        if (array_key_exists('customFields', $translated)) {
            $customFields = $translated['customFields'];
        } else {
            LogHelper::addLog(Level::Error, "Can't get payment method ID", $transaction->getPaymentMethod());
            return null;
        }

        if (!is_array($customFields)
            || !array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID, $customFields)
            || empty($customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID])
        ) {
            LogHelper::addLog(Level::Error, "Can't get payment method ID", $transaction->getPaymentMethod());
            return null;
        }

        return $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID];
    }
}