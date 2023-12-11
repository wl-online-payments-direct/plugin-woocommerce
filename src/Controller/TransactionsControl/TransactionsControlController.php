<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\TransactionsControl;

use Monolog\Logger;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\AdminTranslate;
use MoptWorldline\Service\OrderHelper;
use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentHandler;
use OnlinePayments\Sdk\ValidationException;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @Route(defaults={"_routeScope"={"api"}})
 */
class TransactionsControlController extends AbstractController
{
    private SystemConfigService $systemConfigService;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private OrderTransactionStateHandler $transactionStateHandler;
    private Logger $logger;
    private TranslatorInterface $translator;
    private RequestStack $requestStack;
    private StateMachineRegistry $stateMachineRegistry;

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $orderRepository
     * @param EntityRepository $customerRepository
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param Logger $logger
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     */
    public function __construct(
        SystemConfigService          $systemConfigService,
        EntityRepository             $orderRepository,
        EntityRepository             $customerRepository,
        OrderTransactionStateHandler $transactionStateHandler,
        Logger                       $logger,
        TranslatorInterface          $translator,
        RequestStack                 $requestStack,
        StateMachineRegistry $stateMachineRegistry
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->requestStack = $requestStack;

        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/status",
     *     name="api.action.transactions.control.status",
     *     methods={"POST"}
     * )
     */
    public function status(Request $request, Context $context): JsonResponse
    {
        $success = false;
        $message = AdminTranslate::trans($this->translator->getLocale(), "statusUpdateError");
        $hostedCheckoutId = $request->request->get('transactionId');
        if (!$hostedCheckoutId) {
            $message = AdminTranslate::trans($this->translator->getLocale(), "noTransactionForThisOrder");
            return $this->response($success, $message);
        }
        $handler = $this->getHandler($hostedCheckoutId, $context);
        if ($handler->updatePaymentStatus($hostedCheckoutId)) {
            $success = true;
            $message = AdminTranslate::trans($this->translator->getLocale(), "statusUpdateRequestSent");
        }
        return $this->response($success, $message);
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/capture",
     *     name="api.action.transactions.control.capture",
     *     methods={"POST"}
     * )
     */
    public function capture(Request $request, Context $context): JsonResponse
    {
        return $this->processPayment($request, $context, 'capturePayment');
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/cancel",
     *     name="api.action.transactions.control.cancel",
     *     methods={"POST"}
     * )
     */
    public function cancel(Request $request, Context $context): JsonResponse
    {
        return $this->processPayment($request, $context, 'cancelPayment');
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/refund",
     *     name="api.action.transactions.control.refund",
     *     methods={"POST"}
     * )
     */
    public function refund(Request $request, Context $context): JsonResponse
    {
        return $this->processPayment($request, $context, 'refundPayment');
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/getConfig",
     *     name="api.action.transactions.control.getConfig",
     *     methods={"POST"}
     * )
     */
    public function getConfig(Request $request, Context $context): JsonResponse
    {
        $orderId = $request->request->get('orderId');

        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions.paymentMethod')
            ->addAssociation('salesChannel');
        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, $context)->first();
        /** @var OrderTransactionEntity $transaction */
        $transaction = $orderEntity->getTransactions()->last();
        $customFields = $transaction->getPaymentMethod()->getCustomFields();
        $isFullRedirectMethod = false;
        if (is_array($customFields) && array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID, $customFields)) {
            $isFullRedirectMethod = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID] == Payment::FULL_REDIRECT_PAYMENT_METHOD_ID;
        }

        $salesChannelId = $orderEntity->getSalesChannelId();

        $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $salesChannelId);
        $returnUrl = $adapter->getReturnUrl();
        $apiKey = $orderEntity->getSalesChannel()->getAccessKey();

        return
            new JsonResponse([
                'isFullRedirectMethod' => $isFullRedirectMethod,
                'adminPayFinishUrl' => $returnUrl,
                'adminPayErrorUrl' => $returnUrl,
                'swAccessKey' => $apiKey
            ]);
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/enableButtons",
     *     name="api.action.transactions.control.enableButtons",
     *     methods={"POST"}
     * )
     */
    public function enableButtons(Request $request, Context $context): JsonResponse
    {
        try {
            $hostedCheckoutId = $request->request->get('transactionId');
            $order = OrderHelper::getOrder($context, $this->orderRepository, $hostedCheckoutId);
            $customFields = $order->getCustomFields();
            $log = [];
            if (array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_LOG, $customFields)) {
                foreach ($customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_LOG] as $logId => $logEntity) {
                    $date = date('d-m-Y H:i:s', $logEntity['date']);
                    $amount = $logEntity['amount'] / 100;
                    $log[] = "$logId $date $amount {$logEntity['readableStatus']}";
                }
            }
            $log = implode("\r\n", $log);

            $itemsStatus = [];
            if (array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_ITEMS_STATUS, $customFields)) {
                foreach ($customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_ITEMS_STATUS] as $itemEntity) {
                    $itemEntity['unitPrice'] = $itemEntity['unitPrice'] / 100;
                    $itemsStatus[] = $itemEntity;
                }
            }
            $lockButtons = false;
            if (array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_IS_LOCKED, $customFields)) {
                $lockButtons = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_IS_LOCKED];
            }
            $allowedAmounts = Payment::getAllowed($customFields);

            $adapter = new WorldlineSDKAdapter($this->systemConfigService, $this->logger, $order->getSalesChannelId());
            $partialOperationsEnabled = $adapter->getPluginConfig(Form::PARTIAL_OPERATIONS_ENABLED);
        } catch (\Exception $e) {
            return $this->response(false, $e->getMessage());
        }
        return
            new JsonResponse([
                'success' => true,
                'allowedAmounts' => $allowedAmounts,
                'log' => $log,
                'worldlinePaymentStatus' => $itemsStatus,
                'worldlineLockButtons' => $lockButtons,
                'partialOperationsEnabled' => $partialOperationsEnabled,
            ]);
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/getOneyPaymentOption",
     *     name="api.action.transactions.control.getOneyPaymentOption",
     *     methods={"POST"}
     * )
     */
    public function getOneyPaymentOption(): JsonResponse
    {
        return new JsonResponse([
            'value' => $this->systemConfigService->get(Form::ONEY_PAYMENT_OPTION_FIELD)
        ]);
    }

    /**
     * @Route(
     *     "/api/_action/transactions-control/setOneyPaymentOption",
     *     name="api.action.transactions.control.setOneyPaymentOption",
     *     methods={"POST"}
     * )
     */
    public function setOneyPaymentOption(Request $request): JsonResponse
    {
        $oneyPaymentOption = $request->request->get('oneyPaymentOption');
        $this->systemConfigService->set(Form::ONEY_PAYMENT_OPTION_FIELD, $oneyPaymentOption);
        return new JsonResponse([
            'value' => $oneyPaymentOption
        ]);
    }

    /**
     * @param Request $request
     * @param Context $context
     * @param string $action
     * @return JsonResponse
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function processPayment(Request $request, Context $context, string $action): JsonResponse
    {
        $hostedCheckoutId = $request->request->get('transactionId');
        $itemsChanges = $request->request->all('items');

        $amount = (int)round($request->request->get('amount') * 100);
        if (!$hostedCheckoutId) {
            $message = AdminTranslate::trans($this->translator->getLocale(), "noTransactionForThisOrder");
            return $this->response(false, $message);
        }
        if ($amount < 0) {
            $message = AdminTranslate::trans($this->translator->getLocale(), "wrongAmountRequested");
            return $this->response(false, $message);
        }

        $handler = $this->getHandler($hostedCheckoutId, $context);

        Payment::lockOrder($this->requestStack->getSession(), $handler->getOrderId());
        $message = AdminTranslate::trans($this->translator->getLocale(), "failed");
        try {
            if ($result = $handler->$action($hostedCheckoutId, $amount, $itemsChanges)) {
                $message = AdminTranslate::trans($this->translator->getLocale(), "success");
            }
        } catch (ValidationException $e) {
            $result = false;
            $errors = $e->getErrors();
            $messages = [];
            foreach ($errors as $error) {
                $propertyName = $error->getPropertyName();
                $messages[] = $error->getCode() . ' ' . $error->getMessage() . ($propertyName ? "($propertyName)" : '');
            }
            $message = implode(', ', $messages);
        }
        Payment::unlockOrder($this->requestStack->getSession(), $handler->getOrderId());

        return $this->response($result, $message);
    }

    /**
     * @param bool $success
     * @param string $message
     * @return JsonResponse
     */
    private function response(bool $success, string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => $success,
            'message' => $message
        ]);
    }

    /**
     * @param string $hostedCheckoutId
     * @param Context $context
     * @return PaymentHandler|null
     */
    private function getHandler(string $hostedCheckoutId, Context $context): ?PaymentHandler
    {
        $order = OrderHelper::getOrder($context, $this->orderRepository, $hostedCheckoutId);

        return new PaymentHandler(
            $this->systemConfigService,
            $this->logger,
            $order,
            $this->translator,
            $this->orderRepository,
            $this->customerRepository,
            $context,
            $this->transactionStateHandler,
            $this->stateMachineRegistry
        );
    }
}
