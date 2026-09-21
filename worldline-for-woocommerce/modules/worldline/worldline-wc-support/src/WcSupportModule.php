<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WcSupport;

use Automattic\WooCommerce\Utilities\FeaturesUtil;
use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use WC_Session_Handler;
/**
 * The WooCommerce Support module.
 */
class WcSupportModule implements ExecutableModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container) : bool
    {
        $this->addOrderHposSupport();
        $this->persistEmptiedCartImmediately();
        return \true;
    }
    private function addOrderHposSupport() : void
    {
        \add_action('before_woocommerce_init', static function () {
            if (\class_exists(FeaturesUtil::class)) {
                FeaturesUtil::declare_compatibility('custom_order_tables', MAIN_PLUGIN_FILE);
            }
        });
    }
    /**
     * Writes the session out as soon as the cart is emptied, instead of waiting for `shutdown`.
     *
     * `WC_Session_Handler::save_data()` stores the session as one serialised blob with no
     * version check, so two overlapping requests from the same shopper are a plain lost
     * update: whichever finishes last wins, with its whole snapshot. The order-received page
     * is where that bites. It empties the cart on `template_redirect`, then keeps running -
     * waiting on the per-order lock, then rendering - so without this the emptied cart sits
     * in memory for the rest of the request while the database still holds the full one.
     * A request that overlaps that tail reads the stale cart and writes it back afterwards,
     * and the shopper finds the cart still full.
     *
     * Saving here closes that tail: the empty cart is durable before the page does anything
     * else. Priority 99 puts this after `WC_Cart_Session::destroy_cart_session()` on the same
     * hook, which is what nulls the cart keys we want persisted.
     *
     * Note what this does not do. `save_data()` clears the dirty flag, and nothing after this
     * point reliably sets it again - the keys the later hooks null out are already gone - so
     * the request still writes the session exactly once. This moves that single write earlier
     * rather than adding a second one, which means an overlapping request that read the
     * session before the cart was emptied can still write the full cart back. Closing that
     * half needs the order-received request itself to be short, so that no second request
     * from the same shopper can be in flight across the write at all.
     */
    private function persistEmptiedCartImmediately() : void
    {
        \add_action('woocommerce_cart_emptied', static function () : void {
            $session = \WC()->session;
            if (!$session instanceof WC_Session_Handler) {
                return;
            }
            $session->save_data();
        }, 99);
    }
}
