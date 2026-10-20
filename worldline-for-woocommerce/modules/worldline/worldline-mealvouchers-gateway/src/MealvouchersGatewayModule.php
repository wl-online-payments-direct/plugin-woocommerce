<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\MealvouchersGateway;

use Worldline\Assets\AssetManager;
use Worldline\Assets\Script;
use Worldline\Assets\Style;
use Worldline\Assets\Asset;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\Modularity\Module\ExtendingModule;
use Worldline\Vendor\Psr\Container\ContainerExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Container\NotFoundExceptionInterface;
class MealvouchersGatewayModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    public const PACKAGE_NAME = 'worldline-mealvouchers-gateway';
    /**
     * Makes the gateway reachable for a GUEST on the classic checkout.
     *
     * Mealvouchers is offered as soon as an e-mail is known - `merchantCustomerId` is optional, so a
     * guest qualifies. But WooCommerce's own order-review refresh syncs ONLY the address into the
     * customer session: `WC_AJAX::update_order_review()` sets billing_country/state/postcode/city/
     * address_1/address_2 and the shipping equivalents, and nothing else. So for a guest
     * `WC()->customer->get_email()` stays empty however much they type, and the availability check
     * never sees the e-mail it asks for.
     *
     * Both halves below are needed: the first stores the typed e-mail, the second asks for a refresh
     * when that field changes - without it the method would appear only after the shopper happened to
     * touch an address field afterwards.
     *
     * The third registration is the block checkout's equivalent of the overlay WooCommerce already
     * draws on the classic one, so the payment list never changes under the shopper unannounced.
     *
     * @param ContainerInterface $container
     *
     * @return bool
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action('woocommerce_checkout_update_order_review', [$this, 'syncClassicCheckoutEmail']);
        \add_action('wp_enqueue_scripts', [$this, 'enqueueClassicEmailRefresh'], 20);
        \add_action('wp_enqueue_scripts', [$this, 'enqueueBlockCheckoutLoader'], 20);
        return \true;
    }
    /**
     * Stores the billing e-mail typed on the classic checkout in the customer session.
     *
     * @param string $postData Serialized checkout form.
     *
     * @return void
     */
    public function syncClassicCheckoutEmail($postData) : void
    {
        if (!\function_exists('WC') || \WC()->customer === null) {
            return;
        }
        \parse_str((string) $postData, $fields);
        $email = \sanitize_email((string) ($fields['billing_email'] ?? ''));
        if ($email === '' || !\is_email($email)) {
            return;
        }
        \WC()->customer->set_billing_email($email);
    }
    /**
     * Refreshes the classic checkout when the billing e-mail changes.
     *
     * Classic only: the block checkout resolves payment methods through its own store and refreshes
     * itself, so a page carrying the checkout block is skipped rather than given a listener that would
     * find no `form.checkout` to bind to.
     *
     * @return void
     */
    public function enqueueClassicEmailRefresh() : void
    {
        if (!\wp_script_is('wc-checkout', 'enqueued')) {
            return;
        }
        if (\function_exists('has_block') && \has_block('woocommerce/checkout')) {
            return;
        }
        $handle = 'worldline-mealvouchers-email-sync';
        \wp_register_script($handle, \false, ['jquery', 'wc-checkout'], null, \true);
        \wp_enqueue_script($handle);
        \wp_add_inline_script($handle, "jQuery(function (\$) {\n" . "    \$('form.checkout').on('change', '#billing_email', function () {\n" . "        \$(document.body).trigger('update_checkout');\n" . "    });\n" . "});");
    }
    /**
     * Marks the block checkout's payment section as busy while the cart is being updated.
     *
     * The classic checkout gets this for free - `checkout.js` blocks `.woocommerce-checkout-payment`
     * for the whole of `update_order_review`. The block checkout has no equivalent: its payment step is
     * only disabled while an order is being PLACED (`isProcessing`), not while the cart is refreshing.
     * So when a guest enters an e-mail, the method list silently changes underneath them and
     * Mealvouchers simply appears.
     *
     * `isCustomerDataUpdating()` on the `wc/store/cart` store is true for exactly the Store API
     * `update-customer` request that recomputes the available methods, which makes it the right signal
     * rather than a timer. The overlay covers the whole payment step, as agreed - any cart refresh
     * raises it, not only the one that brings in Mealvouchers.
     *
     * @return void
     */
    public function enqueueBlockCheckoutLoader() : void
    {
        if (!\function_exists('has_block') || !\has_block('woocommerce/checkout')) {
            return;
        }
        // Registered by WooCommerce itself; without it there is no store to subscribe to.
        if (!\wp_script_is('wc-blocks-data-store', 'registered')) {
            return;
        }
        $handle = 'worldline-mealvouchers-block-loader';
        \wp_register_script($handle, \false, ['wp-data', 'wc-blocks-data-store'], null, \true);
        \wp_enqueue_script($handle);
        \wp_add_inline_script($handle, $this->blockCheckoutLoaderScript());
        \wp_register_style($handle, \false);
        \wp_enqueue_style($handle);
        \wp_add_inline_style($handle, $this->blockCheckoutLoaderStyles());
    }
    /**
     * @return string
     */
    private function blockCheckoutLoaderScript() : string
    {
        // A real overlay element rather than a pseudo-element: the payment step is WooCommerce's own
        // markup and already uses ::before for its step number.
        return <<<'JS'
(function (data) {
    if (!data || typeof data.subscribe !== 'function' || typeof data.select !== 'function') {
        return;
    }

    var STEP = '.wc-block-checkout__payment-method';
    var busy = null;

    data.subscribe(function () {
        var cart = data.select('wc/store/cart');

        if (!cart || typeof cart.isCustomerDataUpdating !== 'function') {
            return;
        }

        var updating = !!cart.isCustomerDataUpdating();

        // subscribe() fires on every store tick; only touch the DOM when the state actually flips.
        if (updating === busy) {
            return;
        }

        busy = updating;

        var step = document.querySelector(STEP);

        if (!step) {
            return;
        }

        step.classList.toggle('worldline-is-refreshing', updating);

        var overlay = step.querySelector('.worldline-payment-loader');

        if (updating && !overlay) {
            overlay = document.createElement('div');
            overlay.className = 'worldline-payment-loader';
            overlay.setAttribute('aria-hidden', 'true');
            step.appendChild(overlay);
        } else if (!updating && overlay) {
            overlay.remove();
        }
    });
})(window.wp && window.wp.data);
JS;
    }
    /**
     * @return string
     */
    private function blockCheckoutLoaderStyles() : string
    {
        // WooCommerce's own spinner, so the overlay is indistinguishable from the classic checkout's.
        $spinner = \function_exists('WC') ? \WC()->plugin_url() . '/assets/images/icons/loader.svg' : '';
        return <<<CSS
.wc-block-checkout__payment-method.worldline-is-refreshing {
    position: relative;
}

.wc-block-checkout__payment-method .worldline-payment-loader {
    position: absolute;
    inset: 0;
    z-index: 2;
    /* Translucent background rather than `opacity` on the element: `opacity` would fade the spinner
       along with the backdrop and leave it a grey smudge. */
    background: rgba(255, 255, 255, 0.6);
}

.wc-block-checkout__payment-method .worldline-payment-loader::after {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 2em;
    height: 2em;
    margin: -1em 0 0 -1em;
    background: url({$spinner}) center center no-repeat;
    background-size: cover;
    animation: worldline-payment-spin 1s ease-in-out infinite;
}

@keyframes worldline-payment-spin {
    100% {
        transform: rotate(360deg);
    }
}

@media (prefers-reduced-motion: reduce) {
    .wc-block-checkout__payment-method .worldline-payment-loader::after {
        animation: none;
    }
}
CSS;
    }
    public function services() : array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
        }
        return $services();
    }
    public function extensions() : array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = (require_once \dirname(__DIR__) . '/inc/extensions.php');
        }
        return $extensions();
    }
}
