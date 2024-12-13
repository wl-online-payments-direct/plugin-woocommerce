<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Monolog\Level;
use MoptWorldline\Bootstrap\Form;
use OnlinePayments\Sdk\Domain\CreateHostedCheckoutResponse;
use OnlinePayments\Sdk\Domain\CreatePaymentResponse;
use OnlinePayments\Sdk\Domain\GetHostedTokenizationResponse;
use OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStateHandler;
use Shopware\Core\Checkout\Order\Aggregate\OrderTransaction\OrderTransactionStates;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\StateMachine\StateMachineRegistry;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use Symfony\Contracts\Translation\TranslatorInterface;


class PaymentHandler
{
    private WorldlineSDKAdapter $adapter;
    private OrderEntity $order;
    private TranslatorInterface $translator;
    private EntityRepository $orderRepository;
    private Context $context;
    private OrderTransactionStateHandler $transactionStateHandler;
    private EntityRepository $customerRepository;
    private StateMachineRegistry $stateMachineRegistry;
    private LogHelper $logger;

    /**
     * @param SystemConfigService $systemConfigService
     * @param OrderEntity $order
     * @param TranslatorInterface $translator
     * @param EntityRepository $orderRepository
     * @param EntityRepository $customerRepository
     * @param Context $context
     * @param OrderTransactionStateHandler $transactionStateHandler
     * @param StateMachineRegistry $stateMachineRegistry
     */
    public function __construct(
        SystemConfigService          $systemConfigService,
        OrderEntity                  $order,
        TranslatorInterface          $translator,
        EntityRepository    $orderRepository,
        EntityRepository    $customerRepository,
        Context                      $context,
        OrderTransactionStateHandler $transactionStateHandler,
        StateMachineRegistry $stateMachineRegistry
    )
    {
        $salesChannelId = $order->getSalesChannelId();
        $this->adapter = new WorldlineSDKAdapter($systemConfigService, $salesChannelId);
        $this->logger = new LogHelper($this->adapter);
        $this->logger->setTranslator($translator);
        $this->order = $order;
        $this->translator = $translator;
        $this->orderRepository = $orderRepository;
        $this->customerRepository = $customerRepository;
        $this->context = $context;
        $this->transactionStateHandler = $transactionStateHandler;
        $this->stateMachineRegistry = $stateMachineRegistry;
    }

    /**
     * @return string
     */
    public function getOrderId(): string
    {
        return $this->order->getId();
    }

    /**
     * @param string $hostedCheckoutId
     * @param bool $isFinalize
     * @return int
     * @throws \Exception
     */
    public function updatePaymentStatus(string $hostedCheckoutId, bool $isFinalize = false): int
    {
        $status = $this->updatePaymentTransactionStatus($hostedCheckoutId, $isFinalize);
        //Partial operations can only be done after manual changes. Webhook and status change operations is always full.
        $this->updateOrderTransactionState($status, $hostedCheckoutId, true);

        return $status;
    }

    /**
     * @param int $worldlinePaymentMethodId
     * @param string $token
     * @return CreateHostedCheckoutResponse
     * @throws \Exception
     */
    public function createPayment(int $worldlinePaymentMethodId, string $token = ''): CreateHostedCheckoutResponse
    {
        $criteria = new Criteria([$this->order->getId()]);
        switch ($worldlinePaymentMethodId) {
            case in_array($worldlinePaymentMethodId, PaymentProducts::PAYMENT_PRODUCT_NEED_DETAILS):
            {
                $criteria->addAssociation('lineItems')
                    ->addAssociation('deliveries.positions.orderLineItem')
                    ->addAssociation('orderCustomer.customer')
                    ->addAssociation('orderCustomer.customer.group')
                    ->addAssociation('language.locale')
                    ->addAssociation('billingAddress')
                    ->addAssociation('billingAddress.country')
                    ->addAssociation('deliveries.shippingOrderAddress')
                    ->addAssociation('deliveries.shippingOrderAddress.country');
            }
            case $worldlinePaymentMethodId === PaymentProducts::PAYMENT_PRODUCT_CARTE_BANCAIRE: {
                $criteria->addAssociation('language.locale')
                    ->addAssociation('lineItems');
            }
            default:
            {
                $criteria->addAssociation('language.locale')
                    ->addAssociation('orderCustomer.customer');
            }
        }

        $orderObject = $this->orderRepository->search($criteria, $this->context)->first();

        $amountTotal = (int)round($this->order->getAmountTotal() * 100);
        $currencyISO = OrderHelper::getCurrencyISO($this->order, $this->logger);

        $this->logger->paymentLog($this->order->getOrderNumber(),  'buildingOrder');
        $hostedCheckoutResponse = $this->adapter->createPayment(
            $amountTotal,
            $currencyISO,
            $worldlinePaymentMethodId,
            $orderObject,
            $token
        );
        $hostedCheckoutId = $hostedCheckoutResponse->getHostedCheckoutId();
        $this->saveOrderCustomFields(
            Payment::STATUS_PAYMENT_CREATED[0],
            $hostedCheckoutId,
            [
                'toCaptureOrCancel' => $amountTotal,
                'toRefund' => 0,
            ],
            [
                'id' => $hostedCheckoutId,
                'amount' => $amountTotal,
            ],
            $this->buildOrderItemStatus()
        );
        return $hostedCheckoutResponse;
    }

    /**
     * @param array $iframeData
     * @return CreatePaymentResponse
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function createHostedTokenizationPayment(array $iframeData): CreatePaymentResponse
    {
        $amountTotal = (int)round($this->order->getAmountTotal() * 100);
        $currencyISO = OrderHelper::getCurrencyISO($this->order, $this->logger);

        $this->logger->paymentLog($this->order->getOrderNumber(), 'buildingHostdTokenizationOrder');

        // Change localeId with locale code (hex to de_DE, for example)
        $iframeData[Form::WORLDLINE_CART_FORM_LOCALE] = LocaleHelper::getCode($iframeData[Form::WORLDLINE_CART_FORM_LOCALE]);
        $hostedTokenization = $this->adapter->createHostedTokenization($iframeData);

        $orderObject = null;
        $productId = $hostedTokenization->getToken()->getPaymentProductId();
        if ($productId == PaymentProducts::PAYMENT_PRODUCT_CARTE_BANCAIRE) {
            $criteria = new Criteria([$this->order->getId()]);
            $criteria->addAssociation('lineItems');
            $orderObject = $this->orderRepository->search($criteria, $this->context)->first();
        }

        $hostedTokenizationPaymentResponse = $this->adapter->createHostedTokenizationPayment(
            $amountTotal,
            $currencyISO,
            $iframeData,
            $hostedTokenization,
            $orderObject
        );
        $this->saveCustomerCustomFields($hostedTokenization);

        $responseId = $hostedTokenizationPaymentResponse->getPayment()->getId();
        $id = explode('_', $responseId);
        $statusCode = $hostedTokenizationPaymentResponse->getPayment()->getStatusOutput()->getStatusCode();
        $isDirectSale = false;
        $toCapture = $amountTotal;
        $toRefund = 0;
        if (in_array($statusCode, Payment::STATUS_CAPTURED)) {
            $isDirectSale = true;
            $toCapture = 0;
            $toRefund = $amountTotal;
        }
        $this->saveOrderCustomFields(
            $hostedTokenizationPaymentResponse->getPayment()->getStatusOutput()->getStatusCode(),
            $id[0],
            [
                'toCaptureOrCancel' => $toCapture,
                'toRefund' => $toRefund,
            ],
            [
                'id' => $responseId,
                'amount' => $amountTotal,
            ],
            $this->buildOrderItemStatus($isDirectSale)
        );

        return $hostedTokenizationPaymentResponse;
    }

    /**
     * @param string $hostedCheckoutId
     * @param int $amount
     * @param array $itemsChanges
     * @return array
     * @throws \Exception
     */
    public function capturePayment(string $hostedCheckoutId, int $amount, array $itemsChanges): array
    {
        $status = $this->updatePaymentTransactionStatus($hostedCheckoutId);
        $customFields = $this->order->getCustomFields();

        if (!in_array($status, Payment::STATUS_PENDING_CAPTURE)) {
            $this->logger->paymentLog($this->order->getOrderNumber(), 'operationIsNotPossibleDueToCurrentStatus' . $status, Level::Error);
            return [false, 'failed'];
        }
        if ($amount > $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT]) {
            $this->logger->paymentLog($this->order->getOrderNumber(), 'maxAmountExceeded', Level::Error);
            return [false, 'failed'];
        }

        $newStatus = $status;
        $amounts = [];
        $log = [];
        $isFinal = ($amount == $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT]);
       /* if ($amount != 0 && !OrderHelper::isOrderLocked($customFields)) {
            $captureResponse = $this->adapter->capturePayment($hostedCheckoutId, $amount, $isFinal);
            $this->logger->paymentLog($this->order->getOrderNumber(), 'capturePayment', 0, $captureResponse->toJson());
            $newStatus = $captureResponse->getStatusOutput()->getStatusCode();
            $log = [
                'id' => $captureResponse->getId(),
                'amount' => $amount,
            ];
            $amounts = $this->recalculateAmounts($customFields, $amount, 0, 0);
        }
        $orderItemsStatus = $this->rebuildOrderItemStatus($customFields, $itemsChanges, 'paid');

        $this->saveOrderCustomFields(
            $newStatus,
            $hostedCheckoutId,
            $amounts,
            $log,
            $orderItemsStatus
        );
        $this->updateOrderTransactionState($newStatus, $hostedCheckoutId, $isFinal);*/
        $message = $this->getSuccessMessage($isFinal, $hostedCheckoutId, 'capturePayment');

        /*if ((!in_array($newStatus, Payment::STATUS_CAPTURE_REQUESTED)
            && !in_array($newStatus, Payment::STATUS_CAPTURED))
            && $amount > 0) {
            return [false, 'failed'];
        }*/
        return [true, $message];
    }

    /**
     * @param string $hostedCheckoutId
     * @param int $amount
     * @param array $itemsChanges
     * @return array
     * @throws \Exception
     */
    public function cancelPayment(string $hostedCheckoutId, int $amount, array $itemsChanges): array
    {
        $status = $this->updatePaymentTransactionStatus($hostedCheckoutId);
        $customFields = $this->order->getCustomFields();

        if (!in_array($status, Payment::STATUS_PENDING_CAPTURE)) {
            $this->logger->paymentLog($this->order->getOrderNumber(), 'operationIsNotPossibleDueToCurrentStatus' . $status, Level::Error);
            return [false, 'failed'];
        }

        if ($amount > $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT]) {
            $this->logger->paymentLog($this->order->getOrderNumber(), 'maxAmountExceeded', Level::Error);
            return [false, 'failed'];
        }

        $newStatus = $status;
        $amounts = [];
        $log = [];
        $isFinal = $amount == $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT];
        if ($amount != 0 && !OrderHelper::isOrderLocked($customFields)) {
            $currencyISO = OrderHelper::getCurrencyISO($this->order, $this->logger);
            if ($currencyISO === false) {
                return [false, 'failed'];
            }

            $cancelResponse = $this->adapter->cancelPayment(
                $hostedCheckoutId,
                $amount,
                $currencyISO,
                $isFinal
            );
            $this->logger->paymentLog($this->order->getOrderNumber(), 'cancelPayment', 0, $cancelResponse->toJson());
            $newStatus = $this->adapter->getCancelStatus($cancelResponse);
            $amounts = $this->recalculateAmounts($customFields, 0, $amount, 0);
            $log = [
                'id' => $cancelResponse->getPayment()->getId(),
                'amount' => $amount
            ];
        }
        $orderItemsStatus = $this->rebuildOrderItemStatus($customFields, $itemsChanges, 'canceled');

        $this->saveOrderCustomFields(
            $newStatus,
            $hostedCheckoutId,
            $amounts,
            $log,
            $orderItemsStatus
        );
        $this->updateOrderTransactionState($newStatus, $hostedCheckoutId, $isFinal);

        if (!in_array($newStatus, Payment::STATUS_PAYMENT_CANCELLED) && $amount > 0) {
            return [false, 'failed'];
        }
        return [true, 'success'];
    }

    /**
     * @param string $hostedCheckoutId
     * @param int $amount
     * @param array $itemsChanges
     * @return array
     * @throws \Exception
     */
    public function refundPayment(string $hostedCheckoutId, int $amount, array $itemsChanges): array
    {
        $status = $this->updatePaymentTransactionStatus($hostedCheckoutId);

        if (in_array($status, Payment::STATUS_REFUNDED)) {
            return [false, 'failed'];
        }

        $customFields = $this->order->getCustomFields();
        if ($amount > $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_REFUND_AMOUNT]) {
            $this->logger->paymentLog($this->order->getOrderNumber(), 'maxAmountExceeded', Level::Error);
            return [false, 'failed'];
        }

        $newStatus = $status;
        $amounts = [];
        $log = [];
        if ($amount != 0 && !OrderHelper::isOrderLocked($customFields)) {
            $currencyISO = OrderHelper::getCurrencyISO($this->order, $this->logger);
            if ($currencyISO === false) {
                return [false, 'failed'];
            }

            $orderNumber = $this->order->getOrderNumber();

            $refundResponse = $this->adapter->refundPayment(
                $hostedCheckoutId,
                $amount,
                $currencyISO,
                $orderNumber
            );

            $this->logger->paymentLog($this->order->getOrderNumber(), 'refundPayment', 0, $refundResponse->toJson());
            $newStatus = $this->adapter->getRefundStatus($refundResponse);
            $amounts = $this->recalculateAmounts($customFields, 0, 0, $amount);
            $log = [
                'id' => $refundResponse->getId(),
                'amount' => $amount
            ];
        }
        $orderItemsStatus = $this->rebuildOrderItemStatus($customFields, $itemsChanges, 'refunded');

        $this->saveOrderCustomFields(
            $newStatus,
            $hostedCheckoutId,
            $amounts,
            $log,
            $orderItemsStatus
        );

        $canBeRefundedLater = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_REFUND_AMOUNT] - $amount
            + $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT];
        $isFinalRefund = ($canBeRefundedLater == 0);
        $this->updateOrderTransactionState($newStatus, $hostedCheckoutId, $isFinalRefund);

        if (!in_array($newStatus, Payment::STATUS_REFUND_REQUESTED)
            && !in_array($newStatus, Payment::STATUS_REFUNDED)
            && $amount > 0) {
            return [false, 'failed'];
        }
        return [true, 'success'];
    }

    /**
     * @param string $hostedCheckoutId
     * @param bool $isFinalize
     * @return int
     * @throws \Exception
     */
    private function updatePaymentTransactionStatus(string $hostedCheckoutId, bool $isFinalize = false): int
    {
        $this->logger->paymentLog($this->order->getOrderNumber(), 'gettingPaymentDetails', 0, ['hostedCheckoutId' => $hostedCheckoutId]);
        $paymentDetails = $this->adapter->getPaymentDetails($hostedCheckoutId);

        if ($token = $this->adapter->getRedirectToken($paymentDetails)) {
            $card = PaymentProducts::createRedirectPaymentProduct($token, $paymentDetails);
            $this->saveCustomerCustomFields(null, $token, $card);
        }

        $status = $this->adapter->getStatus($paymentDetails);

        //Check log for any outer actions
        $this->compareLog($paymentDetails);

        //finalize for direct sales case
        $autoCapture = $this->adapter->getPluginConfig(Form::AUTO_CAPTURE);
        if ($isFinalize && $autoCapture == Form::AUTO_CAPTURE_IMMEDIATELY && in_array($status, Payment::STATUS_CAPTURED)) {
            $amountTotal = (int)round($this->order->getAmountTotal() * 100);
            $this->saveOrderCustomFields(
                $status,
                $hostedCheckoutId,
                [
                    'toCaptureOrCancel' => 0,
                    'toRefund' => $amountTotal,
                ],
                [],
                $this->buildOrderItemStatus(true)
            );
        } else {
            $this->saveOrderCustomFields($status, $hostedCheckoutId);
        }

        return $status;
    }

    /**
     * @param array $customFields
     * @param float $captureAmount
     * @param float $cancelAmount
     * @param float $refundAmount
     * @return float[]
     */
    private function recalculateAmounts(array $customFields, float $captureAmount, float $cancelAmount, float $refundAmount): array
    {
        $toCaptureOrCancel = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT];
        $toRefund = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_REFUND_AMOUNT];

        return [
            'toCaptureOrCancel' => $toCaptureOrCancel - $captureAmount - $cancelAmount,
            'toRefund' => $toRefund + $captureAmount - $refundAmount,
        ];
    }

    /**
     * @param bool $isDirectSale
     * @return array
     */
    public function buildOrderItemStatus(bool $isDirectSale = false): array
    {
        $orderId = $this->order->getId();
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociation('lineItems')
            ->addAssociation('deliveries.positions.orderLineItem')
            ->addAssociation('orderCustomer.customer')
            ->addAssociation('orderCustomer.customer.group');
        /** @var OrderEntity $orderEntity */
        $orderEntity = $this->orderRepository->search($criteria, $this->context)->first();
        $isNetPrice = !$orderEntity->getOrderCustomer()->getCustomer()->getGroup()->getDisplayGross();

        $orderItemsStatus = [];
        /** @var OrderLineItemEntity $lineItem */
        foreach ($orderEntity->getLineItems() as $lineItem) {
            [$totalPrice, $quantity, $unitPrice] = WorldlineSDKAdapter::getUnitPrice($lineItem, $isNetPrice);
            $unprocessed = $quantity;
            $paid = 0;
            if ($isDirectSale) {
                $unprocessed = 0;
                $paid = $quantity;
            }
            $orderItemsStatus[$lineItem->getId()] = [
                'id' => $lineItem->getId(),
                'label' => $lineItem->getLabel(),
                'unitPrice' => $unitPrice,
                'unprocessed' => $unprocessed,
                'paid' => $paid,
                'refunded' => 0,
                'canceled' => 0,
            ];
        }

        $shippingPrice = WorldlineSDKAdapter::getShippingPrice($orderEntity->getShippingCosts(), $isNetPrice);
        if ($shippingPrice > 0) {
            $unprocessed = 1;
            $paid = 0;
            if ($isDirectSale) {
                $unprocessed = 0;
                $paid = 1;
            }
            $id = WorldlineSDKAdapter::SHIPPING_LABEL;
            $orderItemsStatus[$id] = [
                'id' => $id,
                'label' => $id,
                'unitPrice' => $shippingPrice,
                'unprocessed' => $unprocessed,
                'paid' => $paid,
                'refunded' => 0,
                'canceled' => 0,
            ];
        }

        return $orderItemsStatus;
    }

    /**
     * @param array $customFields
     * @param array $changes
     * @param string $process
     * @return array
     */
    public function rebuildOrderItemStatus(array $customFields, array $changes, string $process): array
    {
        $original = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_ITEMS_STATUS];
        switch ($process) {
            case 'paid':
            case 'canceled':
            {
                foreach ($changes as $itemChange) {
                    $original[$itemChange['id']][$process] += $itemChange['quantity'];
                    $original[$itemChange['id']]['unprocessed'] -= $itemChange['quantity'];
                }
                break;
            }
            case 'refunded':
            {
                foreach ($changes as $itemChange) {
                    $original[$itemChange['id']][$process] += $itemChange['quantity'];
                    $original[$itemChange['id']]['paid'] -= $itemChange['quantity'];
                }
                break;
            }
        }

        return $original;
    }

    /**
     * @param int $statusCode
     * @param string $hostedCheckoutId
     * @param array $amounts
     * @param array $log
     * @param array $orderItemsStatus
     * @return void
     */
    private function saveOrderCustomFields(
        int    $statusCode,
        string $hostedCheckoutId,
        array  $amounts = [],
        array  $log = [],
        array  $orderItemsStatus = []
    )
    {
        $currentCustomField = $this->order->getCustomFields();
        if (!empty($currentCustomField)) {
            $customFields = $currentCustomField;
        }

        $readableStatus = AdminTranslate::getReadableStatus($this->translator->getLocale(), $statusCode);
        $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID] = $hostedCheckoutId;
        $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS] = (string)$statusCode;
        $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_READABLE_STATUS] = $readableStatus;

        if (!empty($amounts)) {
            $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT] = $amounts['toCaptureOrCancel'];
            $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_REFUND_AMOUNT] = $amounts['toRefund'];
        }

        if (!empty($log)) {
            if (!strpos($log['id'], '_')) {
                $log['id'] .= '_0';
            }
            $log['date'] = time();
            $log['status'] = $statusCode;
            $log['readableStatus'] = $readableStatus;
            $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_LOG][$log['id']] = $log;
        }

        if (!empty($orderItemsStatus)) {
            $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_ITEMS_STATUS] = $orderItemsStatus;
        }

        if (is_null($currentCustomField)
            || $currentCustomField[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS] != (string)$statusCode
            || !empty($log)
            || !empty($amounts)
            || !empty($orderItemsStatus)
        ) {
            $this->updateDatabase($customFields);
        }
    }

    /**
     * @param PaymentDetailsResponse $paymentDetailsResponse
     * @return void
     */
    private function compareLog(PaymentDetailsResponse $paymentDetailsResponse): void
    {
        $customFields = $this->order->getCustomFields();
        $innerLog = $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_LOG];
        $outerLog = $paymentDetailsResponse->getOperations();

        $needToUpdate = false;
        $needToLock = false;
        foreach ($outerLog as $operation) {
            $outerLogId = $operation->getId();
            $outerStatusCode = $operation->getStatusOutput()->getStatusCode();
            $readableStatus = AdminTranslate::getReadableStatus($this->translator->getLocale(), $outerStatusCode);

            if (!array_key_exists($outerLogId, $innerLog)) {
                $needToUpdate = true;
                $externalChange = '';
                if (!empty($innerLog) && !in_array($outerStatusCode, Payment::STATUS_DO_NOT_LOCK)) {
                    $needToLock = true;
                    $externalChange = " EXTERNAL CHANGE!";
                }
                $innerLog[$outerLogId]['id'] = $outerLogId;
                $innerLog[$outerLogId]['amount'] = 0;
                $innerLog[$outerLogId]['status'] = $outerStatusCode;
                $innerLog[$outerLogId]['readableStatus'] = $readableStatus . $externalChange;
                $innerLog[$outerLogId]['date'] = time();
            } elseif ($innerLog[$outerLogId]['status'] != $outerStatusCode) {
                $needToUpdate = true;
                $innerLog[$outerLogId]['status'] = $outerStatusCode;
                $innerLog[$outerLogId]['readableStatus'] = $readableStatus;
                $innerLog[$outerLogId]['date'] = time();
            }
        }

        if ($needToUpdate || $needToLock) {
            $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_LOG] = $innerLog;
            $customFields[Form::CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_IS_LOCKED] = $needToLock;
            $this->updateDatabase($customFields);
            $this->order->setCustomFields($customFields);
        }
    }

    /**
     * @param array $customFields
     * @return void
     */
    private function updateDatabase(array $customFields): void
    {
        $orderId = $this->order->getId();
        $this->orderRepository->update([
            [
                'id' => $orderId,
                'customFields' => $customFields
            ]
        ], $this->context);
    }

    /**
     * @param int $statusCode
     * @param string $hostedCheckoutId
     * @param bool $isFinal
     * @return void
     */
    public function updateOrderTransactionState(int $statusCode, string $hostedCheckoutId, bool $isFinal = false): void
    {
        $orderTransaction = $this->order->getTransactions()->last();
        $orderTransactionId = $orderTransaction->getId();
        $orderTransactionState = OrderTransactionHelper::getState($orderTransactionId);

        if (!$isFinal) {
            switch ($statusCode) {
                case in_array($statusCode, Payment::STATUS_CAPTURED):
                case in_array($statusCode, Payment::STATUS_CAPTURE_REQUESTED):
                {
                    if (Payment::operationImpossible($orderTransactionState, OrderTransactionStates::STATE_PARTIALLY_PAID)) {
                        break;
                    }
                    $this->logger->paymentLog($this->order->getOrderNumber(), 'paymentPaidPartially',0,['status' => $statusCode, 'hostedCheckoutId' => $hostedCheckoutId]);
                    $this->transactionStateHandler->payPartially($orderTransactionId, $this->context);
                    break;
                }
                case in_array($statusCode, Payment::STATUS_REFUND_REQUESTED):
                case in_array($statusCode, Payment::STATUS_REFUNDED):
                {
                    if (Payment::operationImpossible($orderTransactionState, OrderTransactionStates::STATE_PARTIALLY_REFUNDED)) {
                        break;
                    }
                    $this->logger->paymentLog($this->order->getOrderNumber(), 'paymentRefundedPartially',0,['status' => $statusCode, 'hostedCheckoutId' => $hostedCheckoutId]);
                    $this->transactionStateHandler->refundPartially($orderTransactionId, $this->context);
                    break;
                }
                default:
                {
                    break;
                }
            }
            return;
        }

        switch ($statusCode) {
            case in_array($statusCode, Payment::STATUS_PAYMENT_CREATED):
            case in_array($statusCode, Payment::STATUS_PENDING_CAPTURE):
            {
                if (Payment::operationImpossible($orderTransactionState, OrderTransactionStates::STATE_OPEN)) {
                    break;
                }
                $this->logger->paymentLog($this->order->getOrderNumber(), 'paymentOpen',0,  ['status' => $statusCode, 'hostedCheckoutId' => $hostedCheckoutId],);
                $this->transactionStateHandler->reopen($orderTransactionId, $this->context);
                break;
            }
            case in_array($statusCode, Payment::STATUS_CAPTURE_REQUESTED):
            case in_array($statusCode, Payment::STATUS_CAPTURED):
            {
                if (Payment::operationImpossible($orderTransactionState, OrderTransactionStates::STATE_PAID)) {
                    break;
                }
                if ($orderTransactionState === OrderTransactionStates::STATE_PARTIALLY_PAID) {
                    $this->logger->paymentLog($this->order->getOrderNumber(), 'paymentPartiallyToPaid',0,['status' => $statusCode, 'hostedCheckoutId' => $hostedCheckoutId]);
                    OrderTransactionHelper::paidPartiallyToPaid($this->stateMachineRegistry, $this->context, $orderTransactionId);
                } else {
                    $this->logger->paymentLog($this->order->getOrderNumber(), 'paymentPaid',0, ['status' => $statusCode, 'hostedCheckoutId' => $hostedCheckoutId] );
                    $this->transactionStateHandler->paid($orderTransactionId, $this->context);
                }
                break;
            }
            case in_array($statusCode, Payment::STATUS_REFUND_REQUESTED):
            case in_array($statusCode, Payment::STATUS_REFUNDED):
            {
                if (Payment::operationImpossible($orderTransactionState, OrderTransactionStates::STATE_REFUNDED)) {
                    break;
                }
                $this->logger->paymentLog($this->order->getOrderNumber(), 'paymentRefunded',0,['status' => $statusCode, 'hostedCheckoutId' => $hostedCheckoutId]);
                $this->transactionStateHandler->refund($orderTransactionId, $this->context);
                break;
            }
            case in_array($statusCode, Payment::STATUS_PAYMENT_CANCELLED):
            {
                if (Payment::operationImpossible($orderTransactionState, OrderTransactionStates::STATE_CANCELLED)) {
                    break;
                }
                $this->logger->paymentLog($this->order->getOrderNumber(), 'paymentCanceled',0,['status' => $statusCode, 'hostedCheckoutId' => $hostedCheckoutId]);
                $this->transactionStateHandler->cancel($orderTransactionId, $this->context);
                break;
            }
            default:
            {
                break;
            }
        }
    }

    /**
     * @param GetHostedTokenizationResponse|null $hostedTokenization
     * @param string $token
     * @param array $paymentProduct
     * @return void
     */
    private function saveCustomerCustomFields(
        ?GetHostedTokenizationResponse $hostedTokenization,
        string                         $token = '',
        array                          $paymentProduct = []
    )
    {
        $customerId = $this->order->getOrderCustomer()->getCustomerId();
        $customer = $this->customerRepository->search(new Criteria([$customerId]), $this->context);
        $customFields = $customer->first()->getCustomFields();
        $tmpTokenKey = Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_TMP_TOKEN;

        // On payment finalize we don't know was token tmp or not so we save it here
        if (!is_null($hostedTokenization) && $hostedTokenization->getToken()->getIsTemporary()) {
            $tmpToken = $hostedTokenization->getToken()->getId();
            $customFields[$tmpTokenKey] = $tmpToken;
            $this->customerRepository->update([['id' => $customerId,'customFields' => $customFields]], $this->context);
            return;
        }

        // We don't need to save card if token was temporary
        if (is_array($customFields) && array_key_exists($tmpTokenKey, $customFields) && $token == $customFields[$tmpTokenKey]) {
            unset($customFields[$tmpTokenKey]);
            $this->customerRepository->update([['id' => $customerId,'customFields' => $customFields]], $this->context);
            return;
        }

        // New token
        if (empty($token)) {
            [$token, $paymentProduct] = PaymentProducts::buildPaymentProduct($hostedTokenization);
        } // New redirect token
        else {
            $paymentProduct['redirectToken'] = true;
        }

        // Token already exist
        $savedCardKey = Form::CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN;
        if (!is_null($customFields)
            && array_key_exists($savedCardKey, $customFields)
            && array_key_exists($token, $customFields[$savedCardKey])) {
            return;
        }

        // Make same mask style
        if (stripos($paymentProduct['paymentCard'], 'X')) {
            $paymentProduct['paymentCard'] = str_replace('X', '*', $paymentProduct['paymentCard']);
        }

        $customFields[$savedCardKey][$token] = $paymentProduct;

        $this->customerRepository->update([['id' => $customerId,'customFields' => $customFields]], $this->context);
    }

    /**
     * @param bool $isFinal
     * @param string $hostedCheckoutId
     * @param string $action
     * @return string
     */
    private function getSuccessMessage(bool $isFinal, string $hostedCheckoutId, string $action): string
    {
        $message = 'success';
        if (!$isFinal) {
            $methodId = PaymentProducts::getPaymentProductIdByTransactionId($hostedCheckoutId);
            if ($methodId == PaymentProducts::PAYMENT_PRODUCT_TWINTWL and $action == 'capturePayment') {
                $message = 'custom-messages.twintPartialCaptureWarning';
            }
        }

        return $message;
    }
}
