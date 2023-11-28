<?php declare(strict_types=1);

namespace MoptWorldline\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionDefinition;
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
}