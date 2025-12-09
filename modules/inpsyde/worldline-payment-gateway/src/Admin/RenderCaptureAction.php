<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Admin;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\PaymentCaptureValidator;
use WC_Order;
/**
 * Class RenderAuthorizeAction
 */
class RenderCaptureAction
{
    private PaymentCaptureValidator $paymentCaptureValidator;
    public function __construct(PaymentCaptureValidator $paymentCaptureValidator)
    {
        $this->paymentCaptureValidator = $paymentCaptureValidator;
    }
    public function render(array $orderActions, WC_Order $wcOrder) : array
    {
        if (!$this->paymentCaptureValidator->validate($wcOrder)) {
            return $orderActions;
        }
        $orderActions['worldline_capture_order'] = \esc_html__('Capture authorized Worldline payment', 'worldline-for-woocommerce');
        return $orderActions;
    }
}
