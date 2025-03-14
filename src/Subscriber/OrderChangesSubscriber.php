<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Subscriber
 */

namespace MoptWorldline\Subscriber;

use Monolog\Level;
use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\LogHelper;
use MoptWorldline\Service\OrderHelper;
use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class OrderChangesSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;
    private OrderTransactionStateHandler $transactionStateHandler;
    private StateMachineRegistry $stateMachineRegistry;

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $orderRepository
     * @param EntityRepository $customerRepository
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param StateMachineRegistry $stateMachineRegistry
     */
    public function __construct(
        SystemConfigService          $systemConfigService,
        EntityRepository             $orderRepository,
        EntityRepository             $customerRepository,
        RequestStack                 $requestStack,
        TranslatorInterface          $translator,
        OrderTransactionStateHandler $transactionStateHandler,
        StateMachineRegistry         $stateMachineRegistry
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // 22.03.2023 - should be disabled before Worldline will fix status notifications.
            //OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderWritten',
        ];
    }

    /**
     * @param EntityWrittenEvent $event
     * @return void
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function onOrderWritten(EntityWrittenEvent $event): void
    {
        $request = $this->requestStack->getCurrentRequest();

        $uri = $request->getUri();

        $uriArr = explode('/', $uri);
        $newState = $uriArr[count($uriArr) - 1];
        if (is_null($newState)
            || !in_array(
                $newState,
                [StateMachineTransitionActions::ACTION_CANCEL, StateMachineTransitionActions::ACTION_REFUND]
            )
        ) {
            return;
        }

        foreach ($event->getWriteResults() as $result) {
            $orderId = $result->getPrimaryKey();
            if (is_null($orderId)) {
                continue;
            }
            if (Payment::isOrderLocked($this->requestdebugStack->getSession(), $orderId)) {
                continue;
            }

            //For order transaction changes payload is empty
            if (empty($result->getPayload())) {
                $this->processOrder($orderId, $newState, $event->getContext());
                //Order cancel should lead to payment transaction refund.
                //For order changes payload is NOT empty.
            } else {
                $this->processOrder($orderId, StateMachineTransitionActions::ACTION_REFUND, $event->getContext());
            }
        }
    }

    /**
     * @param string $orderId
     * @param string $state
     * @param Context $context
     * @return void
     * @throws \Exception
     */
    private function processOrder(string $orderId, string $state, Context $context)
    {
        if (!$order = $this->getOrder($orderId, $context)) {
            return;
        }
        $customFields = $order->getCustomFields();
        if (!is_array($customFields)) {
            return;
        }
        if (!array_key_exists('payment_transaction_id', $customFields)) {
            return;
        }
        $hostedCheckoutId = $customFields['payment_transaction_id'];

        $order = OrderHelper::getOrder($context, $this->orderRepository, $hostedCheckoutId);

        $paymentHandler = new PaymentHandler(
            $this->systemConfigService,
            $order,
            $this->translator,
            $this->orderRepository,
            $this->customerRepository,
            $context,
            $this->transactionStateHandler,
            $this->stateMachineRegistry
        );
        $customFields = $order->getCustomFields();
        switch ($state) {
            case StateMachineTransitionActions::ACTION_CANCEL:
            {
                Payment::lockOrder($this->requestStack->getSession(), $orderId);
                $amount = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT];
                if ($amount > 0) {
                    $paymentHandler->cancelPayment($hostedCheckoutId, $amount, []);
                }
                break;
            }
            case StateMachineTransitionActions::ACTION_REFUND:
            {
                Payment::lockOrder($this->requestStack->getSession(), $orderId);
                $amount = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_REFUND_AMOUNT];
                if ($amount > 0) {
                    $paymentHandler->refundPayment($hostedCheckoutId, $amount, []);
                }
                break;
            }
            default :
            {
                break;
            }
        }
        Payment::unlockOrder($this->requestStack->getSession(), $orderId);
    }

    /**
     * @param string $orderId
     * @param Context $context
     * @return OrderEntity|mixed
     */
    private function getOrder(string $orderId, Context $context)
    {
        $orders = $this->orderRepository->search(new Criteria([$orderId]), $context);
        /* @var $order OrderEntity */
        foreach ($orders->getElements() as $order) {
            return $order;
        }
        LogHelper::addLog(Level::Error, "There is no order with id = $orderId");
        return false;
    }
}
