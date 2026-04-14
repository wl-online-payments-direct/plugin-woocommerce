<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Admin;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use WC_Order;
class CancelAuthorizationUi
{
    /**
     * Registers hooks for rendering the cancel UI, loading assets and handling AJAX cancel requests.
     */
    public function register() : void
    {
        \add_action('woocommerce_order_item_add_action_buttons', [$this, 'add_cancel_button'], 20, 1);
        \add_action('admin_footer', [$this, 'render_cancel_container_footer']);
        \add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        \add_action('wp_ajax_worldline_cancel_authorization', [$this, 'handle_cancel_ajax']);
    }
    /**
     * Handles the AJAX request for cancellation submission, validates the amount and triggers cancellation processing.
     */
    public function handle_cancel_ajax() : void
    {
        try {
            $orderId = \absint($_POST['order_id'] ?? 0);
            $amount = (float) \str_replace(',', '.', $_POST['amount'] ?? 0);
            $order = \wc_get_order($orderId);
            if (!$order) {
                throw new \Exception('Order not found');
            }
            $amountCents = (int) \round($amount * 100);
            $totals = $this->getAuthorizationTotals($order);
            if ($amountCents <= 0 || $amountCents > $totals['available']) {
                throw new \Exception(\__('Amount to cancel is not correct. Please provide a valid amount.', 'worldline-for-woocommerce'));
            }
            $gateways = \WC_Payment_Gateways::instance()->payment_gateways();
            $gateway = $gateways[$order->get_payment_method()] ?? null;
            if (!$gateway || !\method_exists($gateway, 'process_cancel')) {
                throw new \Exception('Gateway does not support cancellation');
            }
            $isFinal = $amountCents === $totals['available'];
            $gateway->process_cancel($orderId, $amount, $isFinal);
            $order = \wc_get_order($orderId);
            if (!$order) {
                throw new \Exception('Order not found');
            }
            $totalsAfter = $this->getAuthorizationTotals($order);
            \wp_send_json_success(['message' => \__('Cancellation submitted successfully.', 'worldline-for-woocommerce'), 'captured' => $totalsAfter['captured'], 'pendingCapture' => $totalsAfter['pending_capture'], 'cancelled' => $totalsAfter['cancelled'], 'available' => $totalsAfter['available'], 'capturedFormatted' => \wc_price($totalsAfter['captured'] / 100, ['currency' => $order->get_currency()]), 'pendingCaptureFormatted' => \wc_price($totalsAfter['pending_capture'] / 100, ['currency' => $order->get_currency()]), 'cancelledFormatted' => \wc_price($totalsAfter['cancelled'] / 100, ['currency' => $order->get_currency()]), 'availableFormatted' => \wc_price($totalsAfter['available'] / 100, ['currency' => $order->get_currency()])]);
        } catch (\Throwable $e) {
            \wp_send_json_error(['message' => $e->getMessage()]);
        }
    }
    /**
     * Renders the hidden cancel row container in the admin footer for the order edit screen.
     */
    public function render_cancel_container_footer() : void
    {
        if (!$this->is_order_edit_screen()) {
            return;
        }
        $order = $this->get_current_order();
        if (!$order) {
            return;
        }
        $order = $this->get_current_order();
        if (!$order) {
            return;
        }
        echo '<div id="cancel_items_container" class="wc-order-data-row wc-order-cancel-items wc-order-data-row-toggle" style="display:none;">';
        include $this->root_path('inc/admin-views/html-order-cancel.php');
        echo '</div>';
    }
    /**
     * Returns the currently opened WooCommerce order from the admin request.
     */
    private function get_current_order() : ?\WC_Order
    {
        $id = 0;
        if (isset($_GET['id'])) {
            $id = \absint($_GET['id']);
        }
        if (!$id && isset($_GET['post'])) {
            $id = \absint($_GET['post']);
        }
        return $id ? \wc_get_order($id) : null;
    }
    /**
     * Displays the Cancel button when the current order is eligible for cancellation actions.
     */
    public function add_cancel_button(WC_Order $order) : void
    {
        if (!$this->is_order_edit_screen()) {
            return;
        }
        if (!$this->is_authorized_order($order)) {
            return;
        }
        echo '<button type="button" class="button button-secondary do-cancel-items">' . \esc_html__('Cancel', 'worldline-for-woocommerce') . '</button>';
    }
    /**
     * Enqueues admin JavaScript and CSS assets needed for the cancel UI.
     */
    public function enqueue_assets() : void
    {
        if (!$this->is_order_edit_screen()) {
            return;
        }
        $main_plugin_file = $this->root_path('worldline-for-woocommerce.php');
        $base = 'admin-actions-frontend-main';
        $assetPath = $this->root_path("assets/{$base}.asset.php");
        $asset = \file_exists($assetPath) ? require $assetPath : ['dependencies' => [], 'version' => '1.0.0'];
        \wp_enqueue_script('worldline-admin-actions', \plugins_url("assets/{$base}.js", $main_plugin_file), $asset['dependencies'] ?? [], $asset['version'] ?? '1.0.0', \true);
        $cssPath = $this->root_path("assets/{$base}.css");
        if (\file_exists($cssPath)) {
            \wp_enqueue_style('worldline-admin-actions', \plugins_url("assets/{$base}.css", $main_plugin_file), ['woocommerce_admin_styles'], $asset['version'] ?? '1.0.0');
        }
    }
    /**
     * Checks whether the current admin screen is a WooCommerce order edit screen.
     */
    private function is_order_edit_screen() : bool
    {
        if (!\function_exists('get_current_screen')) {
            return \false;
        }
        $screen = \get_current_screen();
        if (!$screen) {
            return \false;
        }
        return \in_array($screen->id, ['shop_order', 'woocommerce_page_wc-orders'], \true);
    }
    /**
     * Resolves an absolute path inside the plugin root.
     */
    private function root_path(string $rel) : string
    {
        return \dirname(__DIR__, 2) . '/' . \ltrim($rel, '/');
    }
    /**
     * Determines whether the order is still in a state where cancellation actions should be available.
     */
    private function is_authorized_order(\WC_Order $order) : bool
    {
        $code = (string) $order->get_meta('_wlop_transaction_status_code', \true);
        $paymentStatus = (string) $order->get_meta('_wlop_payment_status', \true);
        $pendingCapture = (int) $order->get_meta(OrderMetaKeys::PAYMENT_PENDING_CAPTURE_AMOUNT, \true);
        if ($pendingCapture > 0) {
            return \true;
        }
        return $code === '5' || $code === '' && \strtoupper($paymentStatus) === 'AUTHORIZED';
    }
    /**
     * Calculates authorization-related totals used for cancellation validation and UI display.
     */
    private function getAuthorizationTotals(\WC_Order $order) : array
    {
        $total = (int) \round((float) $order->get_total() * 100);
        $captured = (int) $order->get_meta(OrderMetaKeys::PAYMENT_CAPTURED_AMOUNT, \true);
        $cancelled = (int) $order->get_meta(OrderMetaKeys::PAYMENT_CANCELED_AMOUNT, \true);
        $pendingCapture = (int) $order->get_meta(OrderMetaKeys::PAYMENT_PENDING_CAPTURE_AMOUNT, \true);
        if ($total < 0) {
            $total = 0;
        }
        if ($captured < 0) {
            $captured = 0;
        }
        if ($cancelled < 0) {
            $cancelled = 0;
        }
        if ($pendingCapture < 0) {
            $pendingCapture = 0;
        }
        $available = \max(0, $total - $captured - $cancelled - $pendingCapture);
        return ['total' => $total, 'captured' => $captured, 'cancelled' => $cancelled, 'pending_capture' => $pendingCapture, 'available' => $available];
    }
}
