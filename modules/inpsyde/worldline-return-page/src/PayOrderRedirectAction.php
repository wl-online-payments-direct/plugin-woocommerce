<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage;

use WC_Order;
/**
 * Redirects to the pay for order page.
 */
class PayOrderRedirectAction implements StatusActionInterface
{
    public function execute(string $status, WC_Order $wcOrder) : void
    {
        \wp_safe_redirect($wcOrder->get_checkout_payment_url());
        exit;
    }
}
