<?php

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Controller
 */

namespace MoptWorldline\Controller\TransactionsControl;

use Monolog\Logger;
use MoptWorldline\Bootstrap\Form;
use MoptWorldline\Service\AdminTranslate;
use MoptWorldline\Service\Payment;
use MoptWorldline\Service\PaymentHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Core\Kernel;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @RouteScope(scopes={"api"})
 */
class TransactionsControlController extends AbstractController
{
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $orderTransactionRepository;
    private EntityRepositoryInterface $orderRepository;
    private OrderTransactionStateHandler $transactionStateHandler;
    private Logger $logger;
    private TranslatorInterface $translator;
    private RequestStack $requestStack;

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $orderTransactionRepository
     * @param EntityRepositoryInterface $orderRepository
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param Logger $logger
     * @param TranslatorInterface $translator
     * @param RequestStack $requestStack
     */
    public function __construct(
        SystemConfigService          $systemConfigService,
        EntityRepositoryInterface    $orderTransactionRepository,
        EntityRepositoryInterface    $orderRepository,
        OrderTransactionStateHandler $transactionStateHandler,
        Logger                       $logger,
        TranslatorInterface          $translator,
        RequestStack                 $requestStack
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->orderRepository = $orderRepository;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->logger = $logger;
        $this->translator = $translator;
        $this->requestStack = $requestStack;
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
        if (!$hostedCheckoutId = $this->getTransactionId($request)) {
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
     *     "/api/_action/transactions-control/enableButtons",
     *     name="api.action.transactions.control.enableButtons",
     *     methods={"POST"}
     * )
     */
    public function enableButtons(Request $request, Context $context): JsonResponse
    {
        try {
            $hostedCheckoutId = $this->getTransactionId($request);
            $orderTransaction = PaymentHandler::getOrderTransaction($context, $this->orderTransactionRepository, $hostedCheckoutId);
            $fields = $orderTransaction->getCustomFields();
            $status =  $fields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS];
            $allowedActions = Payment::getAllowedActions($status);
        } catch (\Exception $e) {
            return $this->response(false,'');
        }
        return
            new JsonResponse([
                'success' => true,
                'message' => $allowedActions
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
        if (!$hostedCheckoutId = $this->getTransactionId($request)) {
            $message = AdminTranslate::trans($this->translator->getLocale(), "noTransactionForThisOrder");
            return $this->response(false, $message);
        }

        $handler = $this->getHandler($hostedCheckoutId, $context);

        Payment::lockOrder($this->requestStack->getSession(), $handler->getOrderId());
        $message = AdminTranslate::trans($this->translator->getLocale(), "failed");
        if ($result = $handler->$action($hostedCheckoutId)) {
            $message = AdminTranslate::trans($this->translator->getLocale(), "success");
        }
        Payment::unlockOrder($this->requestStack->getSession(), $handler->getOrderId());

        return $this->response($result, $message);
    }

    /**
     * @param Request $request
     * @return false|string
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    private function getTransactionId(Request $request): ?string
    {
        $url = explode('/', $request->request->get('url'));
        $orderId = $url[count($url) - 2];

        $connection = Kernel::getConnection();
        $sql = "SELECT custom_fields  FROM `order_transaction` WHERE order_id = UNHEX('$orderId')";
        $orderTransactionCustomFields = $connection->executeQuery($sql)->fetchAssociative();
        $customFields = json_decode($orderTransactionCustomFields['custom_fields'], true);
        if (!is_array($customFields) || !array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID, $customFields)) {
            return false;
        }

        return $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID];
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
        $orderTransaction = PaymentHandler::getOrderTransaction($context, $this->orderTransactionRepository, $hostedCheckoutId);

        return new PaymentHandler(
            $this->systemConfigService,
            $this->logger,
            $orderTransaction,
            $this->translator,
            $this->orderRepository,
            $this->orderTransactionRepository,
            $context,
            $this->transactionStateHandler
        );
    }
}
