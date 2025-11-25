<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Payment\Cart\AsyncPaymentTransactionStruct;
use Shopware\Core\Checkout\Payment\Cart\PaymentHandler\AsynchronousPaymentHandlerInterface;
use Shopware\Core\Checkout\Payment\PaymentException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\DataBag\RequestDataBag;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use MoptWorldline\Bootstrap\Form;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class Payment implements AsynchronousPaymentHandlerInterface
{

    const FULL_REDIRECT_PAYMENT_METHOD_ID = "moptWorldlineFullRedirect";
    const FULL_REDIRECT_PAYMENT_METHOD_NAME = "Worldline";
    const IFRAME_PAYMENT_METHOD_ID = "moptWorldlineIframe";
    const IFRAME_PAYMENT_METHOD_NAME = "Worldline Iframe";
    const SAVED_CARD_PAYMENT_METHOD_ID = "moptWorldlineSavedCard";
    const SAVED_CARD_PAYMENT_METHOD_NAME = "Worldline saved card";
    const METHODS_LIST = [
        [
            'id' => self::FULL_REDIRECT_PAYMENT_METHOD_ID,
            'name' => self::FULL_REDIRECT_PAYMENT_METHOD_NAME,
            'description' => 'Worldline full redirect payment method',
            'active' => true,
            'logo' => true,
        ],
        [
            'id' => self::IFRAME_PAYMENT_METHOD_ID,
            'name' => self::IFRAME_PAYMENT_METHOD_NAME,
            'description' => 'Worldline Iframe payment method',
            'active' => false,
            'logo' => true,
        ],
        [
            'id' => self::SAVED_CARD_PAYMENT_METHOD_ID,
            'name' => self::SAVED_CARD_PAYMENT_METHOD_NAME,
            'description' => 'Worldline saved card payment method',
            'active' => false,
            'logo' => false,
        ]
    ];

    const FAKE_METHODS_LIST = [
        self::FULL_REDIRECT_PAYMENT_METHOD_ID,
        self::IFRAME_PAYMENT_METHOD_ID,
        self::SAVED_CARD_PAYMENT_METHOD_ID
    ];

    const DIRECT_SALE = 'SALE';
    const FINAL_AUTHORIZATION = 'FINAL_AUTHORIZATION';

    private SystemConfigService $systemConfigService;
    private EntityRepository $orderRepository;
    private EntityRepository $customerRepository;
    private TranslatorInterface $translator;
    private OrderTransactionStateHandler $transactionStateHandler;
    private StateMachineRegistry $stateMachineRegistry;

    public const STATUS_PAYMENT_CREATED = [0];                      //open
    public const STATUS_PENDING_CAPTURE = [4, 5, 56];               //open
    public const STATUS_PAYMENT_CANCELLED = [1, 6, 61, 62, 64, 75]; //cancelled
    public const STATUS_PAYMENT_REJECTED = [2, 57, 59, 73, 83];     //cancelled
    public const STATUS_CAPTURE_REQUESTED = [91, 92, 99];           //paid / paid partially
    public const STATUS_CAPTURED = [9];                             //paid / paid partially
    public const STATUS_REFUND_REQUESTED = [81, 82];                //refunded /refunded partially
    public const STATUS_REFUNDED = [7, 8, 85];                      //refunded

    // Not used
    public const STATUS_PAYMENT_CANCELLATION_DECLINED = [63];
    public const STATUS_REJECTED_CAPTURE = [93];
    public const STATUS_REDIRECTED = [46];
    public const STATUS_AUTHORIZATION_REQUESTED = [50, 51, 55];

    public const POSSIBLE_STATUSES = [
        OrderTransactionStates::STATE_OPEN => [
            OrderTransactionStates::STATE_CANCELLED,
            OrderTransactionStates::STATE_PARTIALLY_PAID,
            OrderTransactionStates::STATE_PAID,
        ],
        OrderTransactionStates::STATE_PARTIALLY_PAID => [
            OrderTransactionStates::STATE_PAID,
            OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
            OrderTransactionStates::STATE_REFUNDED,
        ],
        OrderTransactionStates::STATE_PARTIALLY_REFUNDED => [
            OrderTransactionStates::STATE_REFUNDED,
        ],
        OrderTransactionStates::STATE_PAID => [
            OrderTransactionStates::STATE_PARTIALLY_REFUNDED,
            OrderTransactionStates::STATE_REFUNDED,
        ],
        OrderTransactionStates::STATE_CANCELLED => [
            OrderTransactionStates::STATE_OPEN,
        ],
    ];

    public const STATUS_DO_NOT_LOCK = [63]; //After fix amount customer can repeat operation

    public const CAPTURE_AMOUNT = 'WorldlineCaptureAmount';
    public const REFUND_AMOUNT = 'WorldlineRefundAmount';

    public const STATUS_LABELS = [
        0 => 'created',

        1 => 'cancelled',
        6 => 'cancelled',
        61 => 'cancelled',
        62 => 'cancelled',
        64 => 'cancelled',
        75 => 'cancelled',

        63 => 'cancellationDeclined',

        2 => 'rejected',
        57 => 'rejected',
        59 => 'rejected',
        73 => 'rejected',
        83 => 'rejected',

        93 => 'rejectedCapture',

        46 => 'redirected',

        5 => 'pendingCapture',
        56 => 'pendingCapture',

        50 => 'authorizationRequested',
        51 => 'authorizationRequested',
        55 => 'authorizationRequested',

        4 => 'captureRequested',
        91 => 'captureRequested',
        92 => 'captureRequested',
        99 => 'captureRequested',

        9 => 'captured',

        81 => 'refundRequested',
        82 => 'refundRequested',

        7 => 'refunded',
        8 => 'refunded',
        85 => 'refunded',

        52 => 'unknown',
    ];

    /**
     * @param SystemConfigService $systemConfigService
     * @param EntityRepository $orderRepository
     * @param EntityRepository $customerRepository
     * @param TranslatorInterface $translator
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param StateMachineRegistry $stateMachineRegistry
     */
    public function __construct(
        SystemConfigService          $systemConfigService,
        EntityRepository             $orderRepository,
        EntityRepository             $customerRepository,
        TranslatorInterface          $translator,
        OrderTransactionStateHandler $transactionStateHandler,
        StateMachineRegistry         $stateMachineRegistry
    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->translator = $translator;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param RequestDataBag $dataBag
     * @param SalesChannelContext $salesChannelContext
     * @return RedirectResponse
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    public function pay(AsyncPaymentTransactionStruct $transaction, RequestDataBag $dataBag, SalesChannelContext $salesChannelContext): RedirectResponse
    {
        // Method that sends the return URL to the external gateway and gets a redirect URL back
        try {
            $clientData = $this->getClientData($dataBag);
            switch (OrderTransactionHelper::getWorldlinePaymentMethodId($transaction->getOrderTransaction())) {
                case self::IFRAME_PAYMENT_METHOD_ID:
                case self::SAVED_CARD_PAYMENT_METHOD_ID:
                {
                    $redirectUrl = $this->getHostedTokenizationRedirectUrl(
                        $transaction,
                        $salesChannelContext->getContext(),
                        $clientData
                    );
                    break;
                }
                default:
                {
                    $redirectUrl = $this->getHostedCheckoutRedirectUrl(
                        $transaction,
                        $salesChannelContext->getContext(),
                        $clientData
                    );
                }
            }
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transaction->getOrderTransaction()->getId(),
                'An error occurred during the communication with external payment gateway' . PHP_EOL . $e->getMessage()
            );
        }
        return new RedirectResponse($redirectUrl);
    }

    /**
     * @param RequestDataBag $dataBag
     * @return array
     */
    private function getClientData(RequestDataBag $dataBag)
    {
        $clientData = [];

        foreach (Form::WORLDLINE_CART_FORM_KEYS as $key) {
            if (!is_null($dataBag->get($key))) {
                $clientData[$key] = $dataBag->get($key);
            }
        }

        // Change localeId with locale code (hex to de_DE, for example)
        $clientData[Form::WORLDLINE_CART_FORM_LOCALE] = LocaleHelper::getCode($clientData[Form::WORLDLINE_CART_FORM_LOCALE]);

        return $clientData;
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
        $orderId = $transaction->getOrder()->getId();
        $customFields = $transaction->getOrder()->getCustomFields();
        if (is_array($customFields) && array_key_exists(Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS, $customFields)) {
            $status = (int)$customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS];
            $hostedCheckoutId = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID];

            //We need to make an additional GET call to get current status
            $handler = $this->getHandler($orderId, $salesChannelContext->getContext());
            try {
                $status = $handler->updatePaymentStatus($hostedCheckoutId, true);
            } catch (\Exception $e) {
                $this->finalizeError($transactionId, $e->getMessage());
            }
            if (in_array($status, self::STATUS_PAYMENT_CANCELLED)) {
                throw PaymentException::customerCanceled(
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
            || in_array($status, self::STATUS_PAYMENT_REJECTED)) {
            $this->finalizeError($transactionId, 'Status is ' . $status);
        }
    }

    /**
     * @param $transactionId
     * @param $message
     * @return mixed
     */
    private function finalizeError($transactionId, $message)
    {
        throw PaymentException::asyncFinalizeInterrupted(
            $transactionId,
            $message
        );
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Context $context
     * @param array $customerData
     * @return string
     * @throws \Exception
     */
    private function getHostedCheckoutRedirectUrl(AsyncPaymentTransactionStruct $transaction, Context $context, array $customerData)
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $orderId = $transaction->getOrder()->getId();
        $handler = $this->getHandler($orderId, $context);

        try {
            $worldlinePaymentMethodId = OrderTransactionHelper::getWorldlinePaymentMethodId($transaction->getOrderTransaction());
            $hostedCheckoutResponse = $handler->createPayment($worldlinePaymentMethodId,  $customerData, '');
        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transactionId,
                \sprintf('An error occurred during the communication with Worldline%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        $link = $hostedCheckoutResponse->getRedirectUrl();
        if ($link === null) {
            throw PaymentException::asyncProcessInterrupted($transactionId, 'No redirect link provided by Worldline');
        }

        return $link;
    }

    /**
     * @param AsyncPaymentTransactionStruct $transaction
     * @param Context $context
     * @param array $customerData
     * @return string
     * @throws \Doctrine\DBAL\Driver\Exception
     */
    private function getHostedTokenizationRedirectUrl(AsyncPaymentTransactionStruct $transaction, Context $context, array $customerData)
    {
        $transactionId = $transaction->getOrderTransaction()->getId();
        $orderId = $transaction->getOrder()->getId();
        $handler = $this->getHandler($orderId, $context);

        try {
            if (array_key_exists(Form::WORLDLINE_CART_FORM_HOSTED_TOKENIZATION_ID, $customerData)) {
                $link = $handler->createHostedTokenizationPayment($customerData)->getMerchantAction()->getRedirectData()->getRedirectURL();
            } else {
                $hostedCheckoutResponse = $handler->createPayment(
                    '',
                    $customerData,
                    $customerData[Form::WORLDLINE_CART_FORM_REDIRECT_TOKEN]
                );
                $link = $hostedCheckoutResponse->getRedirectUrl();
            }

        } catch (\Exception $e) {
            throw PaymentException::asyncProcessInterrupted(
                $transactionId,
                \sprintf('An error occurred during the communication with Worldline%s%s', \PHP_EOL, $e->getMessage())
            );
        }

        if ($link === null) {
            throw PaymentException::asyncProcessInterrupted($transactionId, 'No redirect link provided by Worldline');
        }

        return $link;
    }

    /**
     * @param string $orderId
     * @param Context $context
     * @return PaymentHandler
     */
    private function getHandler(string $orderId, Context $context): PaymentHandler
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('transactions');
        $order = $this->orderRepository->search($criteria, $context)->first();

        return new PaymentHandler(
            $this->systemConfigService,
            $order,
            $this->translator,
            $this->orderRepository,
            $this->customerRepository,
            $context,
            $this->transactionStateHandler,
            $this->stateMachineRegistry
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
     * @param array $customFields
     * @return array
     */
    public static function getAllowed(array $customFields): array
    {
        return [
            Payment::CAPTURE_AMOUNT => $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT] / 100,
            Payment::REFUND_AMOUNT => $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_REFUND_AMOUNT] / 100,
        ];
    }


    /**
     * @param string $currentStatus
     * @param string $goalStatus
     * @return bool
     */
    public static function operationImpossible(string $currentStatus, string $goalStatus): bool
    {
        if ($currentStatus === $goalStatus) {
            return true;
        }

        if (!array_key_exists($currentStatus, Payment::POSSIBLE_STATUSES)) {
            return true;
        }

        if (!in_array($goalStatus, Payment::POSSIBLE_STATUSES[$currentStatus])) {
            return true;
        }

        return false;
    }
}
