<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage;

use WC_Order;
interface OrderAccessPolicyInterface
{
    /**
     * Whether the current request may see (and act on) the given order.
     *
     * @param WC_Order|null $order The resolved order, or null when the key matched nothing.
     * @param string $submittedKey The order key supplied by the caller.
     */
    public function isAllowed(?WC_Order $order, string $submittedKey) : bool;
}
