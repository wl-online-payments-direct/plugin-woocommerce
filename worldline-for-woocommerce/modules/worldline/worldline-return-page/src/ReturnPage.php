<?php

declare (strict_types=1);
// phpcs:disable Worldline.CodeQuality.LineLength.TooLong
// phpcs:disable Worldline.CodeQuality.NoAccessors.NoGetter
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage;

use Exception;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Utils\LockerFactoryInterface;
use Worldline\Vendor\Psr\Container\ContainerExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Throwable;
use WC_Order;
class ReturnPage
{
    private const WC_ORDER_KEY = 'wcOrderKey';
    private const FORCE_UPDATE_KEY = 'forceUpdate';
    /**
     * Requests per minute per order accepted by the handler as a whole.
     *
     * A legitimate page load polls 6 times over roughly 10 seconds, so this leaves
     * room for several reloads of the same order-received page.
     */
    private const REQUEST_LIMIT_PER_MINUTE = 30;
    /**
     * Requests per minute per order accepted by the forceUpdate branch.
     *
     * That branch is the expensive one - it triggers a full OrderUpdater run, which
     * costs several outbound Worldline calls and rewrites the order. A legitimate
     * page load uses it exactly once.
     */
    private const FORCE_UPDATE_LIMIT_PER_MINUTE = 5;
    /**
     * How long the order-received page waits for a concurrent writer to finish.
     *
     * Sized against what the webhook actually takes end to end - roughly a second
     * in practice. Deliberately not the shared locker timeout, which is at least
     * 30 seconds: that is a sane ceiling for a background job and far too long to
     * hold up a page the shopper is looking at.
     */
    private const CONCURRENT_WRITE_WAIT_SECONDS = 3.0;
    private const CONCURRENT_WRITE_POLL_MICROSECONDS = 150000;
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
        $this->registerEarlyStatusAction();
        $this->printOutput();
    }
    /**
     * Runs the status action before the order-received template produces output.
     *
     * The only registered action redirects a cancelled order to the pay page, and
     * `wp_safe_redirect()` needs the headers to still be open. Running it from
     * `woocommerce_before_thankyou` is too late: the template has already started
     * rendering, so the shopper sees the order-received page flash by before
     * landing on /order-pay.
     *
     * `template_redirect` runs after the `wp` hook that refreshes the order from
     * Worldline (CheckoutModule), so the status is already current here, and
     * before any template output.
     */
    private function registerEarlyStatusAction() : void
    {
        \add_action('template_redirect', function () : void {
            $wcOrder = $this->orderFromReceivedPage();
            if (!$wcOrder instanceof WC_Order) {
                return;
            }
            $status = $this->settledPaymentStatus($wcOrder);
            $action = $this->locateWithFallback("action.status.{$status}", null);
            if (!$action instanceof StatusActionInterface) {
                return;
            }
            $action->execute($status, $wcOrder);
        }, 20);
    }
    /**
     * The payment status, giving a concurrent writer a brief chance to finish first.
     *
     * Worldline fires its webhook at the same moment it redirects the shopper back,
     * so the two routinely race. The webhook holds the per-order lock while it
     * writes, and `OrderUpdater::update()` is non-blocking - it simply skips when
     * the lock is taken. The page therefore reads a status that is about to change
     * and renders "still processing", only for the polling script to reload a
     * second later and land on the real outcome. That reload is the visible flash.
     *
     * So: when the status is still undecided, wait for the writer to release the
     * lock, then re-read. Acquiring the lock is the probe - when nobody is writing
     * it succeeds on the first attempt and this costs nothing, which is the normal
     * case. Only a genuine race pays the wait, and it is bounded.
     */
    private function settledPaymentStatus(WC_Order $wcOrder) : string
    {
        $status = $this->checkPaymentStatus($wcOrder);
        if ($status !== ReturnPageStatus::PENDING) {
            return $status;
        }
        if (!$this->waitForConcurrentWriter($wcOrder)) {
            return $status;
        }
        // Force a re-read: the writer's changes landed in the database after this
        // request had already loaded the order.
        $wcOrder->read_meta_data(\true);
        return $this->checkPaymentStatus($wcOrder);
    }
    /**
     * Waits until no other process holds this order's lock.
     *
     * @return bool Whether a writer was actually observed, i.e. whether re-reading is worth it.
     */
    private function waitForConcurrentWriter(WC_Order $wcOrder) : bool
    {
        $factory = $this->orderLockerFactory();
        if (!$factory instanceof LockerFactoryInterface) {
            return \false;
        }
        $locker = $factory->create($wcOrder->get_id());
        $deadline = \microtime(\true) + self::CONCURRENT_WRITE_WAIT_SECONDS;
        $observedWriter = \false;
        while (\microtime(\true) < $deadline) {
            if ($locker->lock()) {
                $locker->unlock();
                return $observedWriter;
            }
            $observedWriter = \true;
            \usleep(self::CONCURRENT_WRITE_POLL_MICROSECONDS);
        }
        return \true;
    }
    private function orderLockerFactory() : ?LockerFactoryInterface
    {
        try {
            $factory = $this->container->get('utils.locker.file_based_locker_factory');
        } catch (Throwable $exception) {
            return null;
        }
        return $factory instanceof LockerFactoryInterface ? $factory : null;
    }
    /**
     * The order this order-received page belongs to, when the caller may act on it.
     *
     * Applies the same access policy as the AJAX endpoint, because WooCommerce only
     * runs its own permission ladder later, while rendering the shortcode.
     */
    private function orderFromReceivedPage() : ?WC_Order
    {
        if (!\function_exists('is_order_received_page') || !\is_order_received_page()) {
            return null;
        }
        // phpcs:ignore WordPress.Security.NonceVerification -- Public page; the caller is authorized by the order key plus OrderAccessPolicyInterface, not by a nonce.
        if (!isset($_GET['key']) || !\is_string($_GET['key'])) {
            return null;
        }
        // phpcs:ignore WordPress.Security.NonceVerification -- See above.
        $wcOrderKey = \sanitize_text_field(\wp_unslash($_GET['key']));
        $wcOrder = $this->resolveOrder($wcOrderKey);
        if (!$wcOrder instanceof WC_Order) {
            return null;
        }
        if ($wcOrder->get_payment_method() !== $this->paymentMethodId) {
            return null;
        }
        return $this->accessPolicy()->isAllowed($wcOrder, $wcOrderKey) ? $wcOrder : null;
    }
    /**
     * Reports the payment status of an order, optionally refreshing it from Worldline first.
     *
     * Registered for logged-out visitors too, because guest checkout must be able to
     * finish. Authorization is therefore not "is anyone logged in" but "may this caller
     * act on this order" - delegated to the access policy, which mirrors the permission
     * ladder WooCommerce applies to the order-received page this endpoint serves.
     *
     * Every rejection returns the same generic 403 so a caller cannot tell an unknown
     * order key from someone else's valid one.
     */
    public function handleCheckPaymentStatusAjax() : void
    {
        $wcOrderKey = $this->submittedOrderKey();
        $wcOrder = $this->resolveOrder($wcOrderKey);
        if (!$wcOrder instanceof WC_Order || !$this->accessPolicy()->isAllowed($wcOrder, $wcOrderKey)) {
            $this->sendError(\__('You are not allowed to view this order.', 'worldline-for-woocommerce'), 403);
            return;
        }
        $orderId = $wcOrder->get_id();
        $throttle = $this->throttle();
        if ($throttle->exceeded('req', $orderId, self::REQUEST_LIMIT_PER_MINUTE)) {
            $this->sendTooManyRequests();
            return;
        }
        $forceUpdate = $this->isForceUpdateRequested();
        if ($forceUpdate && $throttle->exceeded('force', $orderId, self::FORCE_UPDATE_LIMIT_PER_MINUTE)) {
            $this->sendTooManyRequests();
            return;
        }
        if ($forceUpdate) {
            $this->runStatusUpdate($wcOrder);
        }
        $paymentStatus = $this->checkPaymentStatus($wcOrder);
        $message = $this->getStatusMessage($paymentStatus);
        \wp_send_json_success(['status' => $paymentStatus, 'message' => $this->renderMessage($message)], 200);
    }
    /**
     * Order key supplied by the caller, or an empty string when none was sent.
     */
    private function submittedOrderKey() : string
    {
        // phpcs:ignore WordPress.Security.NonceVerification -- Public endpoint by design; the caller is authorized by the order key plus OrderAccessPolicyInterface, not by a nonce.
        if (!isset($_POST[self::WC_ORDER_KEY]) || !\is_string($_POST[self::WC_ORDER_KEY])) {
            return '';
        }
        // phpcs:ignore WordPress.Security.NonceVerification -- See above.
        return \sanitize_text_field(\wp_unslash($_POST[self::WC_ORDER_KEY]));
    }
    /**
     * Resolves the order the key points at, or null when it points at nothing.
     */
    private function resolveOrder(string $wcOrderKey) : ?WC_Order
    {
        if ($wcOrderKey === '') {
            return null;
        }
        $wcOrder = \wc_get_order(\wc_get_order_id_by_order_key($wcOrderKey));
        return $wcOrder instanceof WC_Order ? $wcOrder : null;
    }
    private function isForceUpdateRequested() : bool
    {
        // phpcs:ignore WordPress.Security.NonceVerification -- See submittedOrderKey().
        if (!isset($_POST[self::FORCE_UPDATE_KEY]) || !\is_string($_POST[self::FORCE_UPDATE_KEY])) {
            return \false;
        }
        // phpcs:ignore WordPress.Security.NonceVerification -- See submittedOrderKey().
        return \sanitize_text_field(\wp_unslash($_POST[self::FORCE_UPDATE_KEY])) === 'true';
    }
    private function runStatusUpdate(WC_Order $wcOrder) : void
    {
        $statusUpdater = $this->locateWithFallback('status_updater', null);
        if (!$statusUpdater instanceof StatusUpdaterInterface) {
            return;
        }
        $statusUpdater->updateStatus($wcOrder);
    }
    private function accessPolicy() : OrderAccessPolicyInterface
    {
        $policy = $this->locateWithFallback('access_policy', null);
        return $policy instanceof OrderAccessPolicyInterface ? $policy : new WcOrderReceivedAccessPolicy();
    }
    private function throttle() : OrderRequestThrottle
    {
        $throttle = $this->locateWithFallback('throttle', null);
        return $throttle instanceof OrderRequestThrottle ? $throttle : new OrderRequestThrottle();
    }
    private function sendTooManyRequests() : void
    {
        $this->sendError(\__('Too many requests.', 'worldline-for-woocommerce'), 429);
    }
    /**
     * Note the caller must return immediately after this: wp_send_json_error() only
     * exits for real in a WordPress request, not under the unit-test doubles.
     */
    private function sendError(string $message, int $statusCode) : void
    {
        \wp_send_json_error(['message' => $message], $statusCode);
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
    // phpcs:disable Worldline.CodeQuality.FunctionLength.TooLong
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
            $classes = ['worldline-return-page-order-payment-status'];
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
