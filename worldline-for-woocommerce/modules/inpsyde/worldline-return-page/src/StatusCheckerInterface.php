<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage;

use WC_Order;
interface StatusCheckerInterface
{
    /**
     * Returns the status of the return page.
     */
    public function determineStatus(?WC_Order $wcOrder) : string;
}
