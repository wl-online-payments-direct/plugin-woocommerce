<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AmountOfMoneyFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Refund\RefundProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Refund\RefundValidator;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundRedirectPaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundRedirectPaymentProduct900SpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\RefundRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
class WeroRefundProcessor extends RefundProcessor
{
    private MerchantClientInterface $weroApiClient;
    public function __construct(MerchantClientInterface $apiClient, AmountOfMoneyFactory $amountOfMoneyFactory, RefundValidator $refundValidator)
    {
        parent::__construct($apiClient, $amountOfMoneyFactory, $refundValidator);
        $this->weroApiClient = $apiClient;
    }
    /**
     * @throws \Exception
     */
    protected function handleRefund(string $transactionId, AmountOfMoney $amountOfMoney) : void
    {
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $refundReason = isset($_POST['wero_refund_reason']) ? \sanitize_text_field(\wp_unslash($_POST['wero_refund_reason'])) : 'WrongAmountCorrection';
        // phpcs:enable WordPress.Security.NonceVerification.Missing
        $product900Input = new RefundRedirectPaymentProduct900SpecificInput();
        $product900Input->setRefundReason($refundReason);
        $redirectInput = new RefundRedirectPaymentMethodSpecificInput();
        $redirectInput->setRefundRedirectPaymentProduct900SpecificInput($product900Input);
        $refundRequest = new RefundRequest();
        $refundRequest->setAmountOfMoney($amountOfMoney);
        $refundRequest->setRefundRedirectPaymentMethodSpecificInput($redirectInput);
        $this->weroApiClient->payments()->refundPayment($transactionId, $refundRequest);
    }
}
