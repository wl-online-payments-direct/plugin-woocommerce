<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Utils\LockerFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Fee\FeeFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentDetailsResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentOutput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\PaymentStatusOutput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Products\GetPaymentProductParams;
class OrderUpdater
{
    protected MerchantClientInterface $apiClient;
    protected LockerFactoryInterface $lockerFactory;
    protected MoneyAmountConverter $moneyAmountConverter;
    private FeeFactory $feeFactory;
    public function __construct(MerchantClientInterface $apiClient, LockerFactoryInterface $lockerFactory, MoneyAmountConverter $moneyAmountConverter, FeeFactory $feeFactory)
    {
        $this->apiClient = $apiClient;
        $this->lockerFactory = $lockerFactory;
        $this->moneyAmountConverter = $moneyAmountConverter;
        $this->feeFactory = $feeFactory;
    }
    public function lockOrder(WlopWcOrder $wlopWcOrder, callable $callback) : void
    {
        $locker = $this->lockerFactory->create($wlopWcOrder->order()->get_id());
        /**
         * This optimization prevents unnecessary duplicated order requests.
         * Be careful when using locker in other places.
         */
        if ($locker->isLocked()) {
            return;
        }
        $locker->lock();
        try {
            $callback();
        } finally {
            $locker->unlock();
        }
    }
    /**
     * Retrieves and saves the current status from the API,
     * updates WC status/notes if needed.
     */
    public function update(WlopWcOrder $wlopWcOrder) : void
    {
        $this->lockOrder($wlopWcOrder, function () use($wlopWcOrder) : void {
            $paymentDetails = $this->refreshWlopData($wlopWcOrder);
            if ($paymentDetails) {
                $this->addSurchargeIfPossible($wlopWcOrder, $paymentDetails->getPaymentOutput());
                $this->adjustWcStatus($wlopWcOrder, $paymentDetails->getPaymentOutput());
                $this->checkExemptionInfo($wlopWcOrder, $paymentDetails->getPaymentOutput(), $paymentDetails->getStatusOutput());
                $this->updateOrderDetailsMeta($wlopWcOrder, $paymentDetails);
                $this->rebuildPaymentsFromOperations($wlopWcOrder, $paymentDetails);
                $wlopWcOrder->order()->save();
            }
        });
    }
    /**
     * Saves the current status from the given API response,
     * updates WC status/notes if needed.
     */
    public function updateFromResponse(WlopWcOrder $wlopWcOrder, PaymentResponse $paymentResponse) : void
    {
        $this->lockOrder($wlopWcOrder, function () use($wlopWcOrder, $paymentResponse) : void {
            $this->updateStatusMeta($wlopWcOrder, $paymentResponse->getStatusOutput());
            $this->addSurchargeIfPossible($wlopWcOrder, $paymentResponse->getPaymentOutput());
            $this->adjustWcStatus($wlopWcOrder, $paymentResponse->getPaymentOutput());
            $this->checkExemptionInfo($wlopWcOrder, $paymentResponse->getPaymentOutput(), $paymentResponse->getStatusOutput());
            $this->updateOrderDetailsMeta($wlopWcOrder, $paymentResponse);
            $this->rebuildPaymentsFromOperations($wlopWcOrder, $paymentResponse);
            $wlopWcOrder->order()->save();
        });
    }
    /**
     * Rebuilds the per-tender list from PaymentDetailsResponse->getOperations().
     *
     * @param PaymentDetailsResponse|PaymentResponse $response
     */
    private function rebuildPaymentsFromOperations(WlopWcOrder $wlopWcOrder, $response) : void
    {
        if (!\method_exists($response, 'getOperations')) {
            $paymentId = (string) $response->getId();
            if ($paymentId === '') {
                return;
            }
            try {
                $response = $this->apiClient->payments()->getPaymentDetails($this->basePaymentId($paymentId));
            } catch (\Throwable $e) {
                return;
            }
        }
        $operations = $response->getOperations() ?? [];
        if (empty($operations)) {
            return;
        }
        $methodTotals = $this->aggregateOperationTotalsByMethod($operations);
        $methodCaptures = $this->aggregateCapturesByMethod($response);
        $entries = [];
        foreach ($operations as $op) {
            $statusOutput = $op->getStatusOutput();
            $statusCode = $statusOutput ? (int) $statusOutput->getStatusCode() : null;
            if (!$this->isTenderOperationStatus($statusCode)) {
                continue;
            }
            $opId = (string) $op->getId();
            if ($opId === '') {
                continue;
            }
            try {
                $payment = $this->apiClient->payments()->getPayment($opId);
            } catch (\Throwable $e) {
                continue;
            }
            $paymentOutput = $payment->getPaymentOutput();
            if (!$paymentOutput) {
                continue;
            }
            $productId = $this->getPaymentMethodProductId($paymentOutput);
            $methodName = $this->getPaymentMethodName($wlopWcOrder, $productId, $paymentOutput);
            $fraudResult = $this->getPaymentMethodFraudResult($paymentOutput);
            $money = $op->getAmountOfMoney();
            $cardOutput = $paymentOutput->getCardPaymentMethodSpecificOutput();
            $card = $cardOutput ? $cardOutput->getCard() : null;
            $methodOutput = $cardOutput;
            if (!$methodOutput) {
                $methodOutput = $paymentOutput->getMobilePaymentMethodSpecificOutput();
            }
            $threeds = $methodOutput ? $methodOutput->getThreeDSecureResults() : null;
            $sepaOutput = $paymentOutput->getSepaDirectDebitPaymentMethodSpecificOutput();
            $sepaMandateRef = '';
            if ($sepaOutput && $sepaOutput->getPaymentProduct771SpecificOutput()) {
                $sepaMandateRef = (string) $sepaOutput->getPaymentProduct771SpecificOutput()->getMandateReference();
            }
            $tenderAmount = $money ? (int) $money->getAmount() : 0;
            $method = (string) $op->getPaymentMethod();
            $refundedCents = (int) ($methodTotals[$method]['refunded'] ?? 0);
            $pendingRefundCents = (int) ($methodTotals[$method]['pending_refund'] ?? 0);
            $methodCapturedCents = (int) ($methodCaptures[$method] ?? 0);
            $displayStatus = (string) $op->getStatus();
            $displayStatusCode = $statusCode;
            if ($tenderAmount > 0 && $refundedCents >= $tenderAmount) {
                $displayStatus = 'REFUNDED';
                $displayStatusCode = 8;
            } elseif ($pendingRefundCents > 0) {
                $displayStatus = 'PENDING_REFUND';
                $displayStatusCode = 81;
            } elseif ($tenderAmount > 0 && $methodCapturedCents >= $tenderAmount && \in_array($statusCode, [5, 52, 56, 91, 92, 99], \true)) {
                $displayStatus = 'CAPTURED';
                $displayStatusCode = 9;
            }
            $entries[] = ['paymentId' => $opId, 'productId' => $productId, 'methodName' => (string) ($methodName ?? ''), 'amountCents' => $tenderAmount, 'currency' => $money ? (string) $money->getCurrencyCode() : '', 'statusCode' => $displayStatusCode, 'status' => $displayStatus, 'card' => ['bin' => $card ? (string) $card->getBin() : '', 'number' => $card ? (string) $card->getCardNumber() : ''], 'fraudResult' => (string) ($fraudResult ?? ''), 'threeDS' => ['liability' => $threeds ? (string) $threeds->getLiability() : '', 'appliedExemption' => $threeds ? (string) $threeds->getAppliedExemption() : '', 'authenticationStatus' => $threeds ? $this->getThreeDSAuthenticationStatus($threeds->getAuthenticationStatus()) : ''], 'sepaMandateReference' => $sepaMandateRef, 'capturedAmountCents' => $tenderAmount, 'cancelledAmountCents' => 0, 'pendingCaptureAmountCents' => 0, 'refundedAmountCents' => $refundedCents, 'pendingRefundAmountCents' => $pendingRefundCents, 'updatedAt' => \time()];
        }
        if ($entries === []) {
            return;
        }
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENTS, (string) \wp_json_encode($entries));
    }
    /**
     * Walk operations[] and bucket amounts by Worldline paymentMethod string
     * ("redirect", "card", ...) so each tender can be matched to its refunds.
     *
     * @param array $operations
     * @return array<string, array{captured: int, refunded: int, pending_refund: int}>
     */
    private function aggregateOperationTotalsByMethod(array $operations) : array
    {
        $totals = [];
        foreach ($operations as $op) {
            $method = (string) $op->getPaymentMethod();
            if ($method === '') {
                continue;
            }
            $statusOutput = $op->getStatusOutput();
            $statusCode = $statusOutput ? (int) $statusOutput->getStatusCode() : null;
            $bucket = $this->statusBucket($statusCode);
            if ($bucket === null) {
                continue;
            }
            $money = $op->getAmountOfMoney();
            $amount = $money ? (int) $money->getAmount() : 0;
            if (!isset($totals[$method])) {
                $totals[$method] = ['captured' => 0, 'refunded' => 0, 'pending_refund' => 0];
            }
            $totals[$method][$bucket] += $amount;
        }
        return $totals;
    }
    private function statusBucket(?int $statusCode) : ?string
    {
        if ($statusCode === 9) {
            return 'captured';
        }
        if ($statusCode === 8) {
            return 'refunded';
        }
        if ($statusCode === 81) {
            return 'pending_refund';
        }
        return null;
    }
    /**
     * Tender operations are the original payment captures/authorizations.
     * Refund and cancel operations (status codes 8, 81, 1, 6, 61, 62, 64, 75)
     * are sibling entries in operations[] that fold into a tender via paymentMethod.
     */
    /**
     * @param mixed $response
     * @return array<string, int>
     */
    private function aggregateCapturesByMethod($response) : array
    {
        $byMethod = [];
        if (!\is_object($response) || !\method_exists($response, 'getId')) {
            return $byMethod;
        }
        $paymentId = (string) $response->getId();
        if ($paymentId === '') {
            return $byMethod;
        }
        try {
            $capturesResponse = $this->apiClient->captures()->getCaptures($this->basePaymentId($paymentId));
            foreach ((array) $capturesResponse->getCaptures() as $capture) {
                if (\strtoupper((string) $capture->getStatus()) !== 'CAPTURED') {
                    continue;
                }
                $captureOutput = $capture->getCaptureOutput();
                if (!$captureOutput) {
                    continue;
                }
                $method = (string) $captureOutput->getPaymentMethod();
                if ($method === '') {
                    continue;
                }
                $money = $captureOutput->getAmountOfMoney();
                $amount = $money ? (int) $money->getAmount() : 0;
                if ($amount <= 0) {
                    continue;
                }
                $byMethod[$method] = ($byMethod[$method] ?? 0) + $amount;
            }
        } catch (\Throwable $e) {
        }
        return $byMethod;
    }
    private function basePaymentId(?string $id) : string
    {
        if ($id === null || $id === '') {
            return (string) $id;
        }
        if (\str_contains($id, '_')) {
            return $id;
        }
        return \substr($id, 0, -3) . '000';
    }
    private function isTenderOperationStatus(?int $statusCode) : bool
    {
        if ($statusCode === null) {
            return \false;
        }
        $nonTender = [8, 81, 85, 1, 6, 61, 62, 64, 75, 2, 57, 59, 73, 83, 93];
        return !\in_array($statusCode, $nonTender, \true);
    }
    /**
     * @param WlopWcOrder $wlopWcOrder
     * @return PaymentDetailsResponse|PaymentResponse|null
     * @throws Exception
     * phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration.NoReturnType
     */
    protected function refreshWlopData(WlopWcOrder $wlopWcOrder)
    {
        $transactionId = $wlopWcOrder->transactionId();
        // phpcs:disable Inpsyde.CodeQuality.NoElse.ElseFound
        if ($transactionId) {
            $paymentDetails = $this->apiClient->payments()->getPaymentDetails($transactionId);
        } else {
            $paymentDetails = $this->paymentDetailsFromHostedCheckout($wlopWcOrder);
            if ($paymentDetails) {
                $transactionId = $paymentDetails->getId();
                $wlopWcOrder->setTransactionId($transactionId);
            }
        }
        if (!$paymentDetails) {
            return null;
        }
        $statusOutput = $paymentDetails->getStatusOutput();
        if ($statusOutput) {
            $this->updateStatusMeta($wlopWcOrder, $statusOutput);
        }
        return $paymentDetails;
    }
    /**
     * @throws Exception
     */
    protected function paymentDetailsFromHostedCheckout(WlopWcOrder $wlopWcOrder) : ?PaymentResponse
    {
        $hostedCheckoutId = $wlopWcOrder->hostedCheckoutId();
        if (!$hostedCheckoutId) {
            return null;
        }
        $hostedCheckout = $this->apiClient->hostedCheckout()->getHostedCheckout($hostedCheckoutId);
        return $hostedCheckout->getCreatedPaymentOutput()->getPayment();
    }
    protected function checkExemptionInfo(WlopWcOrder $wlopWcOrder, PaymentOutput $paymentOutput, PaymentStatusOutput $statusOutput) : void
    {
        // skip failed/cancelled
        if (!\in_array($statusOutput->getStatusCategory(), ['PENDING_PAYMENT', 'PENDING_MERCHANT', 'PENDING_CONNECT_OR_3RD_PARTY', 'COMPLETED'], \true)) {
            return;
        }
        //skip changing 3DS related data
        if ($statusOutput->getStatusCategory() === 'PENDING_CONNECT_OR_3RD_PARTY' && \in_array($statusOutput->getStatusCode(), [46, 71, 72, 81, 82])) {
            return;
        }
        $methodOutput = $paymentOutput->getCardPaymentMethodSpecificOutput();
        if (!$methodOutput) {
            $methodOutput = $paymentOutput->getMobilePaymentMethodSpecificOutput();
        }
        if (!$methodOutput) {
            return;
        }
        $threedsResults = $methodOutput->getThreeDSecureResults();
        if (!$threedsResults) {
            return;
        }
        $appliedExemption = $threedsResults->getAppliedExemption();
        $liability = $threedsResults->getLiability();
        if ($wlopWcOrder->order()->get_meta(OrderMetaKeys::THREE_D_SECURE_LIABILITY) !== ($liability ?? '') || $wlopWcOrder->order()->get_meta(OrderMetaKeys::THREE_D_SECURE_APPLIED_EXEMPTION) !== ($appliedExemption ?? '')) {
            $wlopWcOrder->addWorldlineOrderNote(\sprintf(
                /* translators: %1$s - newline, %2$s, %3$s - values from the API like 'low-value',  'issuer' */
                \__('3DS results%1$sApplied exemption: %2$s%1$sLiability: %3$s', 'worldline-for-woocommerce'),
                '<br/>',
                $appliedExemption ?: 'na',
                $liability ?: 'na'
            ));
        }
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::THREE_D_SECURE_APPLIED_EXEMPTION, $appliedExemption);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::THREE_D_SECURE_LIABILITY, $liability);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::THREE_D_SECURE_AUTHENTICATION_STATUS, $this->getThreeDSAuthenticationStatus($threedsResults->getAuthenticationStatus()));
    }
    private function getThreeDSAuthenticationStatus(?string $key) : string
    {
        $statusMap = ['Y' => 'Authentication succeeded', 'A' => 'Authentication attempted', 'I' => 'Information only, liability shifted to the merchant', 'N' => 'Authentication failed', 'R' => 'Authentication rejected', 'U' => 'Authentication unavailable', 'C' => 'Authentication required'];
        return $statusMap[$key] ?? '';
    }
    /**
     * @param WlopWcOrder $wlopWcOrder
     * @param PaymentDetailsResponse|PaymentResponse|null $paymentResponse
     * @return void
     */
    protected function updateOrderDetailsMeta(WlopWcOrder $wlopWcOrder, $paymentResponse = null) : void
    {
        $paymentOutput = $paymentResponse->getPaymentOutput();
        $productId = $this->getPaymentMethodProductId($paymentOutput);
        $methodName = $this->getPaymentMethodName($wlopWcOrder, $productId, $paymentOutput);
        $fraudResult = $this->getPaymentMethodFraudResult($paymentOutput);
        $totals = $this->calculateCapturedAndCancelledAmounts($paymentResponse);
        $acquired = $paymentOutput->getAcquiredAmount();
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_METHOD_PRODUCT_ID, $productId);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_METHOD_NAME, $methodName);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_STATUS, $paymentResponse->getStatus());
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_TOTAL_AMOUNT, $acquired->getAmount());
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_CURRENCY_CODE, $acquired->getCurrencyCode());
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_FRAUD_RESULT, $fraudResult);
        $cardOutput = $paymentOutput->getCardPaymentMethodSpecificOutput();
        $card = $cardOutput ? $cardOutput->getCard() : null;
        if ($card) {
            $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_CARD_BIN, $card->getBin());
            $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_CARD_NUMBER, $card->getCardNumber());
        }
        $sepaOutput = $paymentOutput->getSepaDirectDebitPaymentMethodSpecificOutput();
        if ($sepaOutput && $sepaOutput->getPaymentProduct771SpecificOutput()) {
            $mandateReference = $sepaOutput->getPaymentProduct771SpecificOutput()->getMandateReference();
            if (!empty($mandateReference)) {
                $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::SEPA_MANDATE_REFERENCE, $mandateReference);
            }
        }
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_CAPTURED_AMOUNT, $totals['captured']);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_CANCELED_AMOUNT, $totals['cancelled']);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::PAYMENT_PENDING_CAPTURE_AMOUNT, $totals['pending_capture']);
        $this->upsertPaymentEntry($wlopWcOrder, $paymentResponse, $productId, $methodName, $fraudResult, $totals);
    }
    /**
     * @param PaymentDetailsResponse|PaymentResponse $paymentResponse
     * @param array{captured: int, cancelled: int, pending_capture: int, pending_cancel: int} $totals
     */
    private function upsertPaymentEntry(WlopWcOrder $wlopWcOrder, $paymentResponse, ?int $productId, ?string $methodName, ?string $fraudResult, array $totals) : void
    {
        $paymentId = (string) $paymentResponse->getId();
        if ($paymentId === '') {
            return;
        }
        $entry = $this->buildPaymentEntry($paymentResponse, $productId, $methodName, $fraudResult, $totals);
        $order = $wlopWcOrder->order();
        $raw = (string) $order->get_meta(OrderMetaKeys::PAYMENTS);
        $decoded = $raw !== '' ? \json_decode($raw, \true) : [];
        $list = \is_array($decoded) ? $decoded : [];
        $matched = \false;
        foreach ($list as $i => $existing) {
            if (\is_array($existing) && isset($existing['paymentId']) && $existing['paymentId'] === $paymentId) {
                $list[$i] = $entry;
                $matched = \true;
                break;
            }
        }
        if (!$matched) {
            $list[] = $entry;
        }
        $order->update_meta_data(OrderMetaKeys::PAYMENTS, (string) \wp_json_encode(\array_values($list)));
    }
    /**
     * @param PaymentDetailsResponse|PaymentResponse $paymentResponse
     * @param array{captured: int, cancelled: int, pending_capture: int, pending_cancel: int} $totals
     * @return array<string, mixed>
     */
    private function buildPaymentEntry($paymentResponse, ?int $productId, ?string $methodName, ?string $fraudResult, array $totals) : array
    {
        $paymentOutput = $paymentResponse->getPaymentOutput();
        $statusOutput = $paymentResponse->getStatusOutput();
        $acquired = $paymentOutput ? $paymentOutput->getAcquiredAmount() : null;
        $cardOutput = $paymentOutput ? $paymentOutput->getCardPaymentMethodSpecificOutput() : null;
        $card = $cardOutput ? $cardOutput->getCard() : null;
        $methodOutput = $cardOutput;
        if (!$methodOutput && $paymentOutput) {
            $methodOutput = $paymentOutput->getMobilePaymentMethodSpecificOutput();
        }
        $threeds = $methodOutput ? $methodOutput->getThreeDSecureResults() : null;
        $sepaOutput = $paymentOutput ? $paymentOutput->getSepaDirectDebitPaymentMethodSpecificOutput() : null;
        $sepaMandateReference = '';
        if ($sepaOutput && $sepaOutput->getPaymentProduct771SpecificOutput()) {
            $sepaMandateReference = (string) $sepaOutput->getPaymentProduct771SpecificOutput()->getMandateReference();
        }
        return ['paymentId' => (string) $paymentResponse->getId(), 'productId' => $productId, 'methodName' => (string) ($methodName ?? ''), 'amountCents' => $acquired ? (int) $acquired->getAmount() : 0, 'currency' => $acquired ? (string) $acquired->getCurrencyCode() : '', 'statusCode' => $statusOutput ? (int) $statusOutput->getStatusCode() : null, 'status' => (string) $paymentResponse->getStatus(), 'card' => ['bin' => $card ? (string) $card->getBin() : '', 'number' => $card ? (string) $card->getCardNumber() : ''], 'fraudResult' => (string) ($fraudResult ?? ''), 'threeDS' => ['liability' => $threeds ? (string) $threeds->getLiability() : '', 'appliedExemption' => $threeds ? (string) $threeds->getAppliedExemption() : '', 'authenticationStatus' => $threeds ? $this->getThreeDSAuthenticationStatus($threeds->getAuthenticationStatus()) : ''], 'sepaMandateReference' => $sepaMandateReference, 'capturedAmountCents' => (int) ($totals['captured'] ?? 0), 'cancelledAmountCents' => (int) ($totals['cancelled'] ?? 0), 'pendingCaptureAmountCents' => (int) ($totals['pending_capture'] ?? 0), 'updatedAt' => \time()];
    }
    private function getPaymentMethodProductId(PaymentOutput $paymentOutput) : ?int
    {
        $paymentMethod = $paymentOutput->getCardPaymentMethodSpecificOutput() ?? $paymentOutput->getRedirectPaymentMethodSpecificOutput() ?? $paymentOutput->getMobilePaymentMethodSpecificOutput() ?? $paymentOutput->getSepaDirectDebitPaymentMethodSpecificOutput();
        return $paymentMethod !== null ? $paymentMethod->getPaymentProductId() : null;
    }
    private function getPaymentMethodName(WlopWcOrder $wlopWcOrder, ?int $paymentProductId, ?PaymentOutput $paymentOutput) : ?string
    {
        if ($paymentProductId === null) {
            return null;
        }
        $query = new GetPaymentProductParams();
        $query->setCountryCode($this->getCountryCode($wlopWcOrder));
        $query->setCurrencyCode($this->getCurrencyCode($wlopWcOrder));
        $query->setHide(['fields', 'accountsOnFile', 'translations']);
        $product = $this->apiClient->products()->getPaymentProduct($paymentProductId, $query);
        $mobileOutput = $paymentOutput ? $paymentOutput->getMobilePaymentMethodSpecificOutput() : null;
        $network = $mobileOutput ? $mobileOutput->getNetwork() : null;
        $mobileSubbrand = $network ? ' (' . $network . ')' : '';
        return $product !== null ? $product->getDisplayHints()->getLabel() . $mobileSubbrand : null;
    }
    private function getPaymentMethodFraudResult(PaymentOutput $paymentOutput) : ?string
    {
        $paymentMethod = $paymentOutput->getCardPaymentMethodSpecificOutput() ?? $paymentOutput->getRedirectPaymentMethodSpecificOutput() ?? $paymentOutput->getMobilePaymentMethodSpecificOutput() ?? $paymentOutput->getSepaDirectDebitPaymentMethodSpecificOutput();
        if ($paymentMethod === null) {
            return null;
        }
        $fraudResults = $paymentMethod->getFraudResults();
        if ($fraudResults === null) {
            return null;
        }
        return $fraudResults->getFraudServiceResult();
    }
    private function getCountryCode(WlopWcOrder $wlopWcOrder) : ?string
    {
        $country = $wlopWcOrder->order()->get_billing_country();
        if ($country) {
            return $country;
        }
        if (!\is_null(\WC()->customer)) {
            return \WC()->customer->get_billing_country();
        }
        return \WC()->countries->get_base_country();
    }
    private function getCurrencyCode(WlopWcOrder $wlopWcOrder) : ?string
    {
        return $wlopWcOrder->order()->get_currency() ?: \get_woocommerce_currency();
    }
    protected function updateStatusMeta(WlopWcOrder $wlopWcOrder, PaymentStatusOutput $statusOutput) : void
    {
        $statusCode = $statusOutput->getStatusCode();
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::TRANSACTION_STATUS_CODE, (string) $statusCode);
    }
    protected function adjustWcStatus(WlopWcOrder $wlopWcOrder, PaymentOutput $paymentOutput) : void
    {
        $currentStatus = $wlopWcOrder->order()->get_status();
        // update only between early statuses,
        // do not update after manually set (cancelled, completed, ...) or refunded
        if (!\in_array($currentStatus, ['pending', 'on-hold', 'processing', 'failed'], \true)) {
            return;
        }
        $newStatus = $this->determineWcStatus($wlopWcOrder);
        if ($newStatus === null || $currentStatus === $newStatus) {
            return;
        }
        $wlopWcOrder->order()->set_status($newStatus);
        $this->addStatusChangeNote($wlopWcOrder);
        \do_action('wlop.wc_order_status_updated', ['wcOrderId' => $wlopWcOrder->order()->get_id(), 'wcOrder' => $wlopWcOrder->order(), 'status' => $wlopWcOrder->order()->get_status(), 'statusCode' => $wlopWcOrder->statusCode(), 'paymentOutput' => $paymentOutput]);
    }
    // phpcs:disable Generic.Metrics.CyclomaticComplexity.TooHigh
    protected function determineWcStatus(WlopWcOrder $wlopWcOrder) : ?string
    {
        $statusCode = $wlopWcOrder->statusCode();
        if ($statusCode < 0) {
            return null;
        }
        switch ($statusCode) {
            case 4:
            case 46:
            case 51:
                return 'pending';
            // authorized
            case 5:
            case 56:
            case 91:
            case 92:
            case 99:
            case 52:
                return 'on-hold';
            // captured
            case 9:
                return 'processing';
            // cancelled
            case 1:
            case 62:
            case 64:
            case 75:
            // failed
            case 2:
            case 57:
            case 59:
            case 73:
            case 83:
            case 93:
                return 'failed';
            // no status update
            // refund status is automatically updated by WC
            case 6:
            case 61:
            case 7:
            case 71:
            case 8:
            case 81:
            case 85:
                return null;
        }
        \do_action('wlop.unexpected_status_code', ['wcOrderId' => $wlopWcOrder->order()->get_id(), 'statusCode' => $statusCode]);
        return null;
    }
    protected function addStatusChangeNote(WlopWcOrder $wlopWcOrder) : void
    {
        switch ($wlopWcOrder->order()->get_status()) {
            case 'on-hold':
                $wlopWcOrder->addWorldlineOrderNote(\__('Payment authorization accepted, funds can be captured.', 'worldline-for-woocommerce'));
                break;
        }
    }
    protected function addSurchargeIfPossible(WlopWcOrder $wlopWcOrder, PaymentOutput $paymentOutput) : void
    {
        if ($this->creditCardSurchargeExists($wlopWcOrder)) {
            return;
        }
        $surchargeSpecificOutput = $paymentOutput->getSurchargeSpecificOutput();
        if (!$surchargeSpecificOutput) {
            return;
        }
        $surchargeAmountOfMoney = $surchargeSpecificOutput->getSurchargeAmount();
        $centSurcharge = $surchargeAmountOfMoney->getAmount();
        $centSurchargeCurrency = $surchargeAmountOfMoney->getCurrencyCode();
        if ($centSurcharge <= 0) {
            return;
        }
        $decimalSurcharge = $this->moneyAmountConverter->centValueToDecimalValue($centSurcharge, $centSurchargeCurrency);
        if ($decimalSurcharge <= 0) {
            return;
        }
        $fee = $this->feeFactory->create(\__('Surcharge', 'worldline-for-woocommerce'), $decimalSurcharge);
        $order = $wlopWcOrder->order();
        $order->add_item($fee);
        $order->calculate_totals();
    }
    protected function creditCardSurchargeExists(WlopWcOrder $wlopWcOrder) : bool
    {
        $order = $wlopWcOrder->order();
        $orderItems = $order->get_items('fee');
        foreach ($orderItems as $item) {
            $isWlopCreditCardSurcharge = $item->get_meta(FeeFactory::CREDIT_CARD_SURCHARGE_META_KEY);
            if ($isWlopCreditCardSurcharge === '1') {
                return \true;
            }
        }
        return \false;
    }
    private function calculateCapturedAndCancelledAmounts($paymentDetailsResponse) : array
    {
        $totals = ['captured' => 0, 'cancelled' => 0, 'pending_capture' => 0, 'pending_cancel' => 0];
        if (!\is_object($paymentDetailsResponse) || !\method_exists($paymentDetailsResponse, 'getId')) {
            return $totals;
        }
        $paymentId = (string) $paymentDetailsResponse->getId();
        if ($paymentId === '') {
            return $totals;
        }
        try {
            $capturesResponse = $this->apiClient->captures()->getCaptures($paymentId);
            foreach ((array) $capturesResponse->getCaptures() as $capture) {
                $status = \strtoupper((string) $capture->getStatus());
                $money = $capture->getCaptureOutput()->getAmountOfMoney();
                $amount = (int) $money->getAmount();
                if ($amount <= 0) {
                    continue;
                }
                if ($status === 'CAPTURE_REQUESTED') {
                    $totals['pending_capture'] += $amount;
                } elseif ($status === 'CAPTURED') {
                    $totals['captured'] += $amount;
                }
            }
        } catch (\Throwable $e) {
        }
        if (\method_exists($paymentDetailsResponse, 'getOperations')) {
            foreach ((array) $paymentDetailsResponse->getOperations() as $op) {
                $status = \strtoupper((string) $op->getStatus());
                $money = $op->getAmountOfMoney();
                if (!$money) {
                    continue;
                }
                $amount = (int) $money->getAmount();
                if ($amount <= 0) {
                    continue;
                }
                if ($status === 'CANCELLED') {
                    $totals['cancelled'] += $amount;
                } elseif ($status === 'CANCEL_REQUESTED') {
                    $totals['pending_cancel'] += $amount;
                }
            }
        }
        return $totals;
    }
}
