<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage;

/**
 * Transient token bucket for the return-page AJAX endpoint, keyed on the order id.
 *
 * The bucket is deliberately *not* keyed on the WooCommerce session or the client IP.
 * A rate limit must bind to something the abuser cannot rotate:
 * `WC()->session->get_customer_id()` is derived from a request cookie the attacker
 * simply omits, which hands them a fresh bucket on every request (and would in
 * practice only count legitimate shoppers); the client IP rotates just as easily.
 * The order id is attacker-independent and is the natural unit of abuse for this
 * endpoint, since every request must name the order it wants to act on.
 *
 * The bucket is advisory — a cost cap, not a security boundary. Authorization is
 * the boundary and is enforced separately.
 */
class OrderRequestThrottle
{
    /**
     * Counts one request against the given bucket for the given order.
     *
     * @param string $bucket Bucket name, so several independent limits can share one order.
     * @param int $orderId The order the request acts on.
     * @param int $limit Maximum number of requests allowed per minute in this bucket.
     *
     * @return bool True when the limit is already reached (the request must be refused
     *              and is not counted), false when the request is allowed and counted.
     */
    public function exceeded(string $bucket, int $orderId, int $limit) : bool
    {
        $key = 'wlop_rp_' . $bucket . '_' . (string) $orderId;
        $count = (int) \get_transient($key);
        if ($count >= $limit) {
            return \true;
        }
        \set_transient($key, $count + 1, \MINUTE_IN_SECONDS);
        return \false;
    }
}
