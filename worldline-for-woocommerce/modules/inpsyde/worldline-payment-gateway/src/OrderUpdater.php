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
                $wlopWcOrder->order()->save();
            }
        });
    }
    /**
     * Saves the current status from the given API response,
     * updates WC status/notes if needed.
     */
    public function updateFromResponse(WlopWcOrder $wlopWcOrder, PaymentStatusOutput $statusOutput, PaymentOutput $paymentOutput) : void
    {
        $this->lockOrder($wlopWcOrder, function () use($wlopWcOrder, $statusOutput, $paymentOutput) : void {
            $this->updateStatusMeta($wlopWcOrder, $statusOutput);
            $this->addSurchargeIfPossible($wlopWcOrder, $paymentOutput);
            $this->adjustWcStatus($wlopWcOrder, $paymentOutput);
            $this->checkExemptionInfo($wlopWcOrder, $paymentOutput, $statusOutput);
            $wlopWcOrder->order()->save();
        });
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
        // already saved
        if ($wlopWcOrder->order()->get_meta(OrderMetaKeys::THREE_D_SECURE_RESULT_PROCESSED)) {
            return;
        }
        // skip failed/cancelled
        if (!\in_array($statusOutput->getStatusCategory(), ['PENDING_PAYMENT', 'PENDING_MERCHANT', 'PENDING_CONNECT_OR_3RD_PARTY', 'COMPLETED'], \true)) {
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
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::THREE_D_SECURE_APPLIED_EXEMPTION, $appliedExemption);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::THREE_D_SECURE_LIABILITY, $liability);
        $wlopWcOrder->order()->update_meta_data(OrderMetaKeys::THREE_D_SECURE_RESULT_PROCESSED, 'yes');
        $wlopWcOrder->addWorldlineOrderNote(\sprintf(
            /* translators: %1$s - newline, %2$s, %3$s - values from the API like 'low-value',  'issuer' */
            \__('3DS results%1$sApplied exemption: %2$s%1$sLiability: %3$s', 'worldline-for-woocommerce'),
            '<br/>',
            $appliedExemption ?: 'na',
            $liability ?: 'na'
        ));
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
}
