<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\RefundProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Admin\WeroRefundReasonUi;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class HostedCheckoutWeroRefundProcessor implements RefundProcessorInterface
{
    private RefundProcessorInterface $baseRefundProcessor;
    private WeroRefundProcessor $weroRefundProcessor;
    public function __construct(RefundProcessorInterface $baseRefundProcessor, WeroRefundProcessor $weroRefundProcessor)
    {
        $this->baseRefundProcessor = $baseRefundProcessor;
        $this->weroRefundProcessor = $weroRefundProcessor;
    }
    public function refundOrderPayment(WC_Order $order, float $amount, string $reason) : void
    {
        $wlopOrder = new WlopWcOrder($order);
        if ($wlopOrder->paymentMethodName() === WeroRefundReasonUi::WERO_PAYMENT_METHOD_NAME) {
            $this->weroRefundProcessor->refundOrderPayment($order, $amount, $reason);
            return;
        }
        $this->baseRefundProcessor->refundOrderPayment($order, $amount, $reason);
    }
}
