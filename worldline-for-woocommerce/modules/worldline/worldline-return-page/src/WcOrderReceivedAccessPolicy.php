<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage;

use WC_Order;
/**
 * Grants exactly the access WooCommerce itself grants to the order-received page.
 *
 * The return-page polling markup is only ever printed for a viewer WooCommerce has
 * already authorized for that page, so the AJAX endpoint behind it must apply the
 * same permission ladder — no more, no less. This class deliberately *mirrors*
 * WooCommerce's own order-received rules:
 *
 * - `WC_Order::key_is_valid()` (a `hash_equals` comparison),
 * - the `woocommerce_order_received_verify_known_shoppers` filter for customer orders,
 * - the `woocommerce_order_email_verification_grace_period` filter and the WC session
 *   customer email for guest orders,
 * - the `woocommerce_order_email_verification_required` filter for the final guest verdict.
 *
 * The same filter names are used on purpose: a merchant's override then binds
 * identically to the page and to this endpoint, so the two cannot drift apart at
 * site level. Only public WooCommerce API is used — in particular this class does
 * *not* couple to `Automattic\WooCommerce\Internal\Utilities\Users`, which is
 * `@internal` and may change without notice.
 *
 * Because the rules are replicated rather than delegated, they can drift when
 * WooCommerce changes them. Re-check this class against WooCommerce's
 * order-received permission logic on every WooCommerce major upgrade. Note the
 * comparison operators have already drifted: WC 8.6 compared the guest email with
 * `===`, WC 10.9 switched to `strcasecmp` — this class follows the newer behaviour.
 */
class WcOrderReceivedAccessPolicy implements OrderAccessPolicyInterface
{
    /**
     * Context passed to the WooCommerce verification filters, matching the page we mirror.
     */
    private const CONTEXT = 'order-received';
    public function isAllowed(?WC_Order $order, string $submittedKey) : bool
    {
        if (!$order instanceof WC_Order) {
            return \false;
        }
        if (!$order->key_is_valid($submittedKey)) {
            return \false;
        }
        $customerId = (int) $order->get_customer_id();
        if ($customerId > 0) {
            return $this->isAllowedForKnownShopper($customerId);
        }
        return !$this->guestVerificationRequired($order);
    }
    /**
     * Orders belonging to a registered customer are visible to that customer only,
     * unless the site opts out of verifying known shoppers.
     */
    private function isAllowedForKnownShopper(int $customerId) : bool
    {
        $verifyKnownShoppers = (bool) \apply_filters('woocommerce_order_received_verify_known_shoppers', \true);
        if (!$verifyKnownShoppers) {
            return \true;
        }
        return \get_current_user_id() === $customerId;
    }
    /**
     * Final guest verdict, routed through the same filter WooCommerce uses.
     */
    private function guestVerificationRequired(WC_Order $order) : bool
    {
        $required = !$this->guestIsIdentified($order);
        return (bool) \apply_filters('woocommerce_order_email_verification_required', $required, $order, self::CONTEXT);
    }
    /**
     * Whether a guest caller is identified well enough that no email verification is needed.
     */
    private function guestIsIdentified(WC_Order $order) : bool
    {
        $billingEmail = (string) $order->get_billing_email();
        // Verification makes no sense for an order that carries no billing email at all.
        if ($billingEmail === '') {
            return \true;
        }
        if ($this->isWithinGracePeriod($order)) {
            return \true;
        }
        $sessionEmail = $this->sessionCustomerEmail();
        if ($sessionEmail !== '' && \strcasecmp($sessionEmail, $billingEmail) === 0) {
            return \true;
        }
        return (bool) \current_user_can('read_private_shop_orders');
    }
    /**
     * Whether the order was created recently enough to skip verification.
     */
    private function isWithinGracePeriod(WC_Order $order) : bool
    {
        $dateCreated = $order->get_date_created();
        // Partially built orders can have no creation date; treat that as "outside the grace period".
        if ($dateCreated === null) {
            return \false;
        }
        $gracePeriod = (int) \apply_filters('woocommerce_order_email_verification_grace_period', 10 * \MINUTE_IN_SECONDS, $order, self::CONTEXT);
        return \time() - $dateCreated->getTimestamp() <= $gracePeriod;
    }
    /**
     * Customer email held in the WooCommerce session, or an empty string when there is none.
     *
     * WooCommerce may not be booted (or may have no session) when this runs, so every
     * step degrades to "no match" instead of failing.
     */
    private function sessionCustomerEmail() : string
    {
        $session = \function_exists('WC') ? \WC()->session ?? null : null;
        if (!\is_object($session) || !\method_exists($session, 'get')) {
            return '';
        }
        $customer = $session->get('customer');
        if (!\is_array($customer) || !\is_string($customer['email'] ?? null)) {
            return '';
        }
        return $customer['email'];
    }
}
