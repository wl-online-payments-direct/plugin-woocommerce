<?php declare(strict_types=1);

namespace MoptWorldline\Subscriber;

use Monolog\Logger;
use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\Helper;
use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentHandler;
use Psr\Log\LogLevel;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\OrderEvents;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\Aggregation\StateMachineTransition\StateMachineTransitionActions;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\RouteRequest\HandlePaymentMethodRouteRequestEvent;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\Context;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class OrderChangesSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private Logger $logger;
    private RequestStack $requestStack;
    private TranslatorInterface $translator;
    private OrderTransactionStateHandler $transactionStateHandler;
    private Session $session;

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $orderRepository
     * @param EntityRepository $customerRepository
     * @param Logger $logger
     * @param RequestStack $requestStack
     * @param TranslatorInterface $translator
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param Session $session
     */
    public function __construct(
        SystemConfigService          $systemConfigService,
        EntityRepository             $orderRepository,
        EntityRepository             $customerRepository,
        Logger                       $logger,
        RequestStack                 $requestStack,
        TranslatorInterface          $translator,
        OrderTransactionStateHandler $transactionStateHandler,
        Session                      $session
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->translator = $translator;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->session = $session;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            HandlePaymentMethodRouteRequestEvent::class => 'setIframeFields',
            // 22.03.2023 - should be disabled before Worldline will fix status notifications.
            //OrderEvents::ORDER_WRITTEN_EVENT => 'onOrderWritten',
        ];
    }

    /**
     * @param HandlePaymentMethodRouteRequestEvent $event
     * @return void
     */
    public function setIframeFields(HandlePaymentMethodRouteRequestEvent $event)
    {
        $iframeData = [];
        foreach (Form::WORLDLINE_CART_FORM_KEYS as $key) {
            $iframeData[$key] = $event->getStorefrontRequest()->request->get($key);
            if (is_null($iframeData[$key])) {
                return;
            }
        }
        $this->session->set(Form::SESSION_IFRAME_DATA, $iframeData);
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
            if (Payment::isOrderLocked($this->requestStack->getSession(), $orderId)) {
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

        $order = PaymentHandler::getOrder($context, $this->orderRepository, $hostedCheckoutId);

        $paymentHandler = new PaymentHandler(
            $this->systemConfigService,
            $this->logger,
            $order,
            $this->translator,
            $this->orderRepository,
            $this->customerRepository,
            $context,
            $this->transactionStateHandler
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
        $this->logger->log(LogLevel::ERROR, "There is no order with id = $orderId");
        return false;
    }
}
