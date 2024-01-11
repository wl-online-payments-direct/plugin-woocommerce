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
     * @return string|null
     */
    public static function getWorldlinePaymentMethodId(OrderTransactionEntity $transaction): ?string
    {
        $customFields = $transaction->getPaymentMethod()->getCustomFields();

        if (!is_array($customFields)
            || !array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID, $customFields)
            || empty($customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID])
        ) {
            return self::getCustomFieldFromTransaction($transaction);
        }

        return $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID];
    }

    /**
     * #35519 Special case for other language subshop have no custom field
     * @param OrderTransactionEntity $transaction
     * @return mixed|null
     */
    private static function getCustomFieldFromTransaction(OrderTransactionEntity $transaction): ?string
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