<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage;

use WC_Order;
interface StatusActionInterface
{
    /**
     * Performs some actions when the return page has the given status.
     */
    public function execute(string $status, WC_Order $wcOrder): void;
}
