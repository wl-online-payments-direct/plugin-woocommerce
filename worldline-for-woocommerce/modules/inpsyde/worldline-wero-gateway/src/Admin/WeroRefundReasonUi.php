<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WeroGateway\Admin;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use WC_Order;
class WeroRefundReasonUi
{
    public const WERO_PAYMENT_METHOD_NAME = 'Wero';
    public function register() : void
    {
        \add_action('woocommerce_order_item_add_action_buttons', [$this, 'renderRefundReasonDropdown']);
        \add_action('admin_footer', [$this, 'renderRefundReasonScript']);
    }
    /**
     * @param WC_Order $order
     */
    public function renderRefundReasonDropdown($order) : void
    {
        $wlopOrder = new WlopWcOrder($order);
        if (!$order instanceof WC_Order) {
            return;
        }
        if ($order->get_payment_method() !== GatewayIds::WERO && $wlopOrder->paymentMethodName() !== self::WERO_PAYMENT_METHOD_NAME) {
            return;
        }
        echo '<input type="hidden" id="is_wero_order" value="1" />';
    }
    public function renderRefundReasonScript() : void
    {
        $screen = \get_current_screen();
        if ($screen === null || $screen->id !== 'shop_order' && $screen->id !== 'woocommerce_page_wc-orders') {
            return;
        }
        $reasons = ['WrongAmountCorrection', 'PreDisputeRefund', 'SubscriptionRefund', 'ServiceLateCancellation', 'Other'];
        $options = '';
        foreach ($reasons as $reason) {
            $options .= '<option value="' . \esc_attr($reason) . '">' . \esc_html($reason) . '</option>';
        }
        $label = \esc_html__('Reason for refund (Wero):', 'worldline-for-woocommerce');
        $tooltip = \esc_attr__('Required refund reason sent to Wero', 'worldline-for-woocommerce');
        ?>
        <script type="text/javascript">
            jQuery(function ($) {
                if (!$('#is_wero_order').length) {
                    return;
                }

                var $refundReasonRow = $('#refund_reason').closest('tr');
                if ($refundReasonRow.length) {
                    var $row = $('<tr class="wero-refund-reason-row">' +
                        '<td class="label"><span class="woocommerce-help-tip" data-tip="<?php 
        echo $tooltip;
        ?>"></span><label for="wero_refund_reason"><?php 
        echo $label;
        ?></label></td>' +
                        '<td class="total"><select id="wero_refund_reason" name="wero_refund_reason"><?php 
        echo $options;
        ?></select></td>' +
                        '</tr>');
                    $refundReasonRow.after($row);
                    $row.find('.woocommerce-help-tip').tipTip({ attribute: 'data-tip', fadeIn: 50, fadeOut: 50, delay: 200 });
                    $refundReasonRow.after($row);
                }

                if (typeof $.ajaxPrefilter === 'function') {
                    $.ajaxPrefilter(function (options) {
                        var dataStr = typeof options.data === 'string'
                            ? options.data
                            : (options.data instanceof URLSearchParams ? options.data.toString() : '');
                        if (dataStr.indexOf('action=woocommerce_refund_line_items') !== -1) {
                            var reason = $('#wero_refund_reason').val();
                            if (reason) {
                                options.data += '&wero_refund_reason=' + encodeURIComponent(reason);
                            }
                        }
                    });
                }
            });
        </script>
        <?php 
    }
}
