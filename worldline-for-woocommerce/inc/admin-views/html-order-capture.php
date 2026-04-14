<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
\defined('ABSPATH') || exit;
/** @var \WC_Order $order */
$currency = $order->get_currency();
$currencySymbol = \get_woocommerce_currency_symbol($currency);
$totalCents = (int) \round((float) $order->get_total() * 100);
$capturedCents = (int) $order->get_meta(OrderMetaKeys::PAYMENT_CAPTURED_AMOUNT, \true);
$cancelledCents = (int) $order->get_meta(OrderMetaKeys::PAYMENT_CANCELED_AMOUNT, \true);
$pendingCaptureCents = (int) $order->get_meta(OrderMetaKeys::PAYMENT_PENDING_CAPTURE_AMOUNT, \true);
$availableCents = \max(0, $totalCents - $capturedCents - $cancelledCents - $pendingCaptureCents);
?>
<div class="wl-capture-panel">

    <div class="wl-capture-body">
        <table class="wc-order-totals">
            <tr>
                <td class="label"><?php 
\esc_html_e('Amount already captured:', 'worldline-for-woocommerce');
?></td>
                <td class="total">
                    <span class="wl-capture-already"> <?php 
echo \wc_price($capturedCents / 100, ['currency' => $currency]);
?>
                    </span>
                </td>
            </tr>

            <tr>
                <td class="label"><?php 
\esc_html_e('Amount pending capture:', 'worldline-for-woocommerce');
?></td>
                <td class="total">
                    <span class="wl-capture-pending">
                        <?php 
echo \wc_price($pendingCaptureCents / 100, ['currency' => $currency]);
?>
                    </span>
                </td>
            </tr>

            <tr>
                <td class="label"><?php 
\esc_html_e('Total available to capture:', 'worldline-for-woocommerce');
?></td>
                <td class="total"> <span class="wl-capture-available"> <?php 
echo \wc_price($availableCents / 100, ['currency' => $currency]);
?> </span>
                </td>
            </tr>
            <tr>
                <td class="label">
                    <label for="wl_capture_amount"><?php 
\esc_html_e('Capture amount:', 'worldline-for-woocommerce');
?></label>
                </td>
                <td class="total">
                    <input type="text" id="wl_capture_amount" class="input-text wl-capture-amount" value="" inputmode="decimal" style="width:120px;">
                </td>
            </tr>
        </table>
    </div>

    <div class="wl-capture-footer">
        <div class="wl-capture-footer-left">
            <button type="button" class="button wl-capture-close capture-capture">
                <?php 
\esc_html_e('Cancel', 'worldline-for-woocommerce');
?>
            </button>
        </div>

        <div class="wl-capture-footer-right">
            <button type="button" class="button button-primary wl-capture-submit">
                <?php 
echo \wp_kses_post(\__('Capture', 'worldline-for-woocommerce') . ' <span class="wl-capture-btn-total">0.00</span> ' . \esc_html($currencySymbol));
?>
            </button>
        </div>
    </div>

</div>
<?php 
