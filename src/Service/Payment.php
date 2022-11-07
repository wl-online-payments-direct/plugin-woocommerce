<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Shopware\Core\Checkout\Payment\Exception\CustomerCanceledAsyncPaymentException;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentProcessException;
use Shopware\Core\Checkout\Payment\Exception\AsyncPaymentFinalizeException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Monolog\Logger;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use MoptWorldline\Bootstrap\Form;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Payment implements AsynchronousPaymentHandlerInterface
{
    private SystemConfigService $systemConfigService;
    private EntityRepositoryInterface $orderTransactionRepository;
    private EntityRepositoryInterface $orderRepository;
    private TranslatorInterface $translator;
    private Logger $logger;
    private OrderTransactionStateHandler $transactionStateHandler;

    public const STATUS_PAYMENT_CREATED = [0];                  //open
    public const STATUS_PAYMENT_CANCELLED = [1, 6, 61, 62, 64, 75]; //cancelled
    public const STATUS_PAYMENT_REJECTED = [2, 57, 59, 73, 83]; //failed
    public const STATUS_REJECTED_CAPTURE = [93];                //fail
    public const STATUS_REDIRECTED = [46];                      //
    public const STATUS_PENDING_CAPTURE = [5, 56];              //open
    public const STATUS_AUTHORIZATION_REQUESTED = [50, 51, 55]; //
    public const STATUS_CAPTURE_REQUESTED = [4, 91, 92, 99];    //in progress
    public const STATUS_CAPTURED = [9];                         //paid
    public const STATUS_REFUND_REQUESTED = [81, 82];            //in progress
    public const STATUS_REFUNDED = [7, 8, 85];                  //refunded

    public const CANCEL_ALLOWED = 'WorldlineBtnCancel';
    public const REFUND_ALLOWED = 'WorldlineBtnRefund';
    public const CAPTURE_ALLOWED = 'WorldlineBtnCapture';

    public const STATUS_LABELS = [
        0 => 'created',

        1  => 'cancelled',
        6  => 'cancelled',
        61 => 'cancelled',
        62 => 'cancelled',
        64 => 'cancelled',
        75 => 'cancelled',

        2  => 'rejected',
        57 => 'rejected',
        59 => 'rejected',
        73 => 'rejected',
        83 => 'rejected',

        93 => 'rejectedCapture',

        46 => 'redirected',

        5  => 'pendingCapture',
        56 => 'pendingCapture',

        50 => 'authorizationRequested',
        51 => 'authorizationRequested',
        55 => 'authorizationRequested',

        4  => 'captureRequested',
        91 => 'captureRequested',
        92 => 'captureRequested',
        99 => 'captureRequested',

        9 => 'captured',

        81 => 'refundRequested',
        82 => 'refundRequested',

        7  => 'refunded',
        8  => 'refunded',
        85 => 'refunded',
    ];

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepositoryInterface $orderTransactionRepository
     * @param EntityRepositoryInterface $orderRepository
     * @param TranslatorInterface $translator
     * @param Logger $logger
     * @param OrderTransactionStateHandler $transactionStateHandler
     */
    public function __construct(
        SystemConfigService          $systemConfigService,
        EntityRepositoryInterface    $orderTransactionRepository,
        EntityRepositoryInterface    $orderRepository,
        TranslatorInterface          $translator,
        Logger                       $logger,
        OrderTransactionStateHandler $transactionStateHandler
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderTransactionRepository = $orderTransactionRepository;
        $this->orderRepository = $orderRepository;
        $this->translator = $translator;
        $this->logger = $logger;
        $this->transactionStateHandler = $transactionStateHandler;
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param RequestDataBag $dataBag
     * @param SalesChannelContext $salesChannelContext
     * @return RedirectResponse
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $redirectUrl = $this->sendReturnUrlToExternalGateway($transaction, $salesChannelContext->getContext());
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Request $request
     * @param SalesChannelContext $salesChannelContext
     * @return void
     */
    public function finalize(
        AsyncPaymentTransactionStruct $transaction,
        Request                       $request,
        SalesChannelContext           $salesChannelContext
    ): void
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $customFields = $transaction->getOrderTransaction()->getCustomFields();
        if (is_array($customFields) && array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS, $customFields)) {
            $status = (int)$customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS];
            $hostedCheckoutId = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID];
            //For 0 status we need to make an additional GET call to be sure
            if (in_array($status, self::STATUS_PAYMENT_CREATED)) {
                $handler = $this->getHandler($transactionId, $salesChannelContext->getContext());
                try {
                    $status = $handler->updatePaymentStatus($hostedCheckoutId);
                } catch (\Exception $e) {
                    $this->finalizeError($transactionId, $e->getMessage());
                }
            }
            if (in_array($status, self::STATUS_PAYMENT_CANCELLED)) {
                throw new CustomerCanceledAsyncPaymentException(
                    $transactionId,
                    "Payment canceled"
                );
            }
            $this->checkSuccessStatus($transactionId, $status);
        } else {
            $this->finalizeError($transactionId, "Payment status unknown");
        }
    }

    /**
     * @param string $transactionId
     * @param int $status
     * @return void
     */
    private function checkSuccessStatus(string $transactionId, int $status)
    {
        if (in_array($status, self::STATUS_PAYMENT_CREATED)
            || in_array($status, self::STATUS_PAYMENT_REJECTED))
        {
            $this->finalizeError($transactionId, 'Status is '. $status);
        }
    }

    /**
     * @param $transactionId
     * @return mixed
     */
    private function finalizeError($transactionId, $message)
    {
        throw new AsyncPaymentFinalizeException(
            $transactionId,
            $message
        );
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Context $context
     * @return string
     * @throws \Exception
     */
    private function sendReturnUrlToExternalGateway(AsyncPaymentTransactionStruct $transaction, Context $context)
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $handler = $this->getHandler($transactionId, $context);

        try {
            $customFields = $transaction->getOrderTransaction()->getPaymentMethod()->getCustomFields();
            $worldlinePaymentMethodId = 0;
            if (is_array($customFields) && array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID, $customFields)) {
                $worldlinePaymentMethodId = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID];
            }

            $hostedCheckoutResponse = $handler->createPayment($worldlinePaymentMethodId);
        } catch (\Exception $e) {
            throw new AsyncPaymentProcessException(
                $transactionId,
                \sprintf('An error occurred during the communication with Worldline%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $link = $hostedCheckoutResponse->getRedirectUrl();
        if ($link === null) {
            throw new AsyncPaymentProcessException($transactionId, 'No redirect link provided by Worldline');
        }

        return $link;
    }

    /**
     * @param string $transactionId
     * @param Context $context
     * @return PaymentHandler
     */
    private function getHandler(string $transactionId, Context $context): PaymentHandler
    {
        $criteria = new Criteria([$transactionId]);
        $criteria->addAssociation('order');
        $orderTransaction = $this->orderTransactionRepository->search($criteria, $context)->first();

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

    /**
     * @param string $orderId
     * @param SessionInterface $session
     * @return void
     */
    public static function lockOrder(SessionInterface $session, string $orderId)
    {
        $session->set(Form::SESSION_OPERATIONS_LOCK . $orderId, true);
    }

    /**
     * @param string $orderId
     * @param SessionInterface $session
     * @return void
     */
    public static function unlockOrder(SessionInterface $session, string $orderId)
    {
        $session->set(Form::SESSION_OPERATIONS_LOCK . $orderId, false);
    }

    /**
     * @param string $orderId
     * @param SessionInterface $session
     * @return bool
     */
    public static function isOrderLocked(SessionInterface $session, string $orderId): bool
    {
        return $session->get(Form::SESSION_OPERATIONS_LOCK . $orderId, false);
    }

    /**
     * @param int $status
     * @return array
     */
    public static function getAllowedActions(int $status): array
    {
        switch ($status) {
            case in_array($status, self::STATUS_PENDING_CAPTURE):{
                return [self::CANCEL_ALLOWED, self::CAPTURE_ALLOWED];
            }
            case in_array($status, self::STATUS_CAPTURED): {
                return [self::REFUND_ALLOWED];
            }
            default: {
                return [];
            }
        }
    }
}
