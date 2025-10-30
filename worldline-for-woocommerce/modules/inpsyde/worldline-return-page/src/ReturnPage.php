<?php

declare (strict_types=1);
// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
// phpcs:disable Inpsyde.CodeQuality.NoAccessors.NoGetter
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage;

use Exception;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use WC_Order;
class ReturnPage
{
    private const WC_ORDER_KEY = 'wcOrderKey';
    private const FORCE_UPDATE_KEY = 'forceUpdate';
    private string $paymentMethodId;
    private ContainerInterface $container;
    public function __construct(string $paymentMethodId, ContainerInterface $container)
    {
        $this->paymentMethodId = $paymentMethodId;
        $this->container = $container;
    }
    public function init() : void
    {
        $this->registerAjax();
        $this->printOutput();
    }
    public function handleCheckPaymentStatusAjax() : void
    {
        // nonce check should not be needed here, not modifying anything
        // phpcs:disable WordPress.Security.NonceVerification
        $wcOrder = null;
        if (isset($_POST[self::WC_ORDER_KEY]) && \is_string($_POST[self::WC_ORDER_KEY])) {
            $wcOrderKey = \sanitize_text_field(\wp_unslash($_POST[self::WC_ORDER_KEY]));
            $wcOrderId = \wc_get_order_id_by_order_key($wcOrderKey);
            $wcOrder = \wc_get_order($wcOrderId);
            if (!$wcOrder instanceof WC_Order) {
                $wcOrder = null;
            }
        }
        if (isset($_POST[self::FORCE_UPDATE_KEY]) && $_POST[self::FORCE_UPDATE_KEY] === 'true') {
            $statusUpdater = $this->locateWithFallback('status_updater', null);
            if ($statusUpdater instanceof StatusUpdaterInterface) {
                $statusUpdater->updateStatus($wcOrder);
            }
        }
        $paymentStatus = $this->checkPaymentStatus($wcOrder);
        $message = $this->getStatusMessage($paymentStatus);
        \wp_send_json_success(['status' => $paymentStatus, 'message' => $this->renderMessage($message)], 200);
    }
    public function checkPaymentStatus(?WC_Order $wcOrder) : string
    {
        $statusChecker = $this->locateWithFallback('status_checker', null);
        if (!$statusChecker instanceof StatusCheckerInterface) {
            throw new Exception('status_checker not defined.');
        }
        return $statusChecker->determineStatus($wcOrder);
    }
    public function getStatusMessage(string $status) : string
    {
        return (string) \apply_filters("syde.return_page.{$this->paymentMethodId}.message.status.{$status}", $this->locateWithFallback("message.status.{$status}", "{$status}"));
    }
    /**
     * @param string $key
     * @param mixed $fallback
     * @return mixed
     */
    private function locateWithFallback(string $key, $fallback)
    {
        try {
            return $this->container->get($this->generateServiceName($key));
        } catch (ContainerExceptionInterface $exception) {
            return $fallback;
        }
    }
    private function generateServiceName(string $key) : string
    {
        return 'return_page.' . $this->paymentMethodId . '.' . $key;
    }
    private function ajaxEndpointName() : string
    {
        return 'return-page-' . $this->paymentMethodId . '-check-payment-status';
    }
    // phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
    private function printOutput() : void
    {
        \add_action('woocommerce_before_thankyou', function (int $orderId) : void {
            $order = \wc_get_order($orderId);
            if (!$order instanceof WC_Order) {
                throw new Exception("Failed to retrieve the order based on the provided order ID {$orderId}.");
            }
            $orderPaymentMethodId = $order->get_payment_method();
            if ($orderPaymentMethodId !== $this->paymentMethodId) {
                return;
            }
            $outputParameters = \apply_filters("syde.return_page.{$this->paymentMethodId}.parameters", ['timeout' => $this->locateWithFallback('interval', 1000), 'retryCount' => $this->locateWithFallback('retry_count', 5), 'messageLoading' => $this->locateWithFallback('message.loading', 'Processing your payment. Please wait...'), 'action' => $this->ajaxEndpointName()]);
            $status = $this->checkPaymentStatus($order);
            $isDone = $status !== ReturnPageStatus::PENDING;
            $action = $this->locateWithFallback("action.status.{$status}", null);
            if ($action instanceof StatusActionInterface) {
                $action->execute($status, $order);
            }
            $classes = ['syde-return-page-order-payment-status'];
            if ($isDone) {
                $classes[] = 'done';
            }
            $classes = \apply_filters("syde.return_page.{$this->paymentMethodId}.html_classes", $classes);
            \assert(\is_array($classes));
            $classesStr = \implode(' ', $classes);
            $message = (string) $outputParameters['messageLoading'];
            if ($isDone) {
                $message = $this->getStatusMessage($status);
            }
            echo \sprintf('<div class="%s" data-timeout="%d"
                                    data-retry-count="%d"
                                    data-action="%s"
                                    >', \esc_attr($classesStr), (int) $outputParameters['timeout'], (int) $outputParameters['retryCount'], \esc_attr((string) $outputParameters['action']));
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
            echo $this->renderMessage($message);
            echo "</div>";
        });
    }
    private function renderMessage(string $message) : string
    {
        $renderer = $this->locateWithFallback('message.render', new ReturnPageRender());
        \assert($renderer instanceof ReturnPageRenderInterface);
        return $renderer->render(['message' => $message]);
    }
    public function registerAjax() : void
    {
        \add_action('wp_ajax_nopriv_' . $this->ajaxEndpointName(), [$this, 'handleCheckPaymentStatusAjax']);
        \add_action('wp_ajax_' . $this->ajaxEndpointName(), [$this, 'handleCheckPaymentStatusAjax']);
    }
}
