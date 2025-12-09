<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage;

use Exception;
use WC_Order;
interface StatusUpdaterInterface
{
    /**
     * Performs the operations needed to update the currently saved status
     * (e.g. requesting it via API).
     *
     * @throws Exception
     */
    public function updateStatus(?WC_Order $wcOrder) : void;
}
