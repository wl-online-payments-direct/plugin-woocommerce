<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
class WebhookHelper
{
    /**
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public static function reference(WebhooksEvent $webhook) : ?string
    {
        $payment = $webhook->getPayment();
        $refund = $webhook->getRefund();
        $output = null;
        if ($payment) {
            $output = $payment->getPaymentOutput();
        } elseif ($refund) {
            $output = $refund->getRefundOutput();
        }
        if (!$output) {
            return null;
        }
        $ref = $output->getReferences();
        if (!$ref) {
            return null;
        }
        return $ref->getMerchantReference();
    }
    /**
     * @psalm-suppress RedundantConditionGivenDocblockType
     */
    public static function transactionId(WebhooksEvent $webhook) : ?string
    {
        $payment = $webhook->getPayment();
        $refund = $webhook->getRefund();
        if ($payment) {
            return $payment->getId();
        } elseif ($refund) {
            return $refund->getId();
        }
        return null;
    }
    public static function statusCode(WebhooksEvent $webhook) : ?int
    {
        $payment = $webhook->getPayment();
        $refund = $webhook->getRefund();
        $output = null;
        if ($payment) {
            $output = $payment->getStatusOutput();
        } elseif ($refund) {
            $output = $refund->getStatusOutput();
        }
        if (!$output) {
            return null;
        }
        return $output->getStatusCode();
    }
    public static function transactionIdForUi(WebhooksEvent $webhook) : string
    {
        $transactionId = self::transactionId($webhook);
        if (\is_null($transactionId)) {
            return \__('Unknown transaction ID', 'worldline-for-woocommerce');
        }
        return $transactionId;
    }
    public static function paymentCapturedAmount(WebhooksEvent $webhook) : ?AmountOfMoney
    {
        $payment = $webhook->getPayment();
        if (!$payment) {
            return null;
        }
        $output = $payment->getPaymentOutput();
        if (!$output) {
            return null;
        }
        return $output->getAcquiredAmount();
    }
    public static function cancelledPaymentAmount(WebhooksEvent $webhook) : ?AmountOfMoney
    {
        if ($webhook->type !== 'payment.cancelled') {
            return null;
        }
        $payment = $webhook->getPayment();
        if (!$payment) {
            return null;
        }
        if ($payment->getStatus() !== 'CANCELLED') {
            return null;
        }
        $paymentOutput = $payment->getPaymentOutput();
        if (!$paymentOutput) {
            return null;
        }
        return $paymentOutput->getAmountOfMoney();
    }
    public static function paymentRefundedAmount(WebhooksEvent $webhook) : ?AmountOfMoney
    {
        $refund = $webhook->getRefund();
        if (!$refund) {
            return self::cancelledPaymentAmount($webhook);
        }
        $output = $refund->getRefundOutput();
        if (!$output) {
            return null;
        }
        return $output->getAmountOfMoney();
    }
}
