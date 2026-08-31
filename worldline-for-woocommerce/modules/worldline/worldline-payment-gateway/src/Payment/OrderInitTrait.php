<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use WC_Order;
trait OrderInitTrait
{
    protected function initWlopWcOrder(WC_Order $wcOrder) : void
    {
        if ($wcOrder->get_status() === 'failed') {
            $wcOrder->set_status('pending');
        }
        /** Some webhooks arrive almost at the same time. There is a high possibility
         * for 1st and 2nd webhook to create two separate meta fields with the same key,
         * because they are both unaware that the data is about to be saved. We
         * save empty values for these meta-fields, so that can't happen.
         *
         * update_meta_data(), not add_meta_data(): this method runs again on every
         * payment retry from /order-pay. add_meta_data() appends, and get_meta()
         * returns the FIRST row, so a retry would keep reading the first attempt's
         * values - a stale transaction id (which then makes a refresh re-read the
         * abandoned payment and fail an order that actually succeeded) and a stale
         * creation time (which the session-timeout sweep matches, failing an order
         * while the shopper is still paying). update_meta_data() creates the row
         * when absent and overwrites it afterwards, so the anti-race placeholder
         * still works and retries stay consistent.
         */
        $wcOrder->update_meta_data(OrderMetaKeys::TRANSACTION_STATUS_CODE, '-1');
        $wcOrder->update_meta_data(OrderMetaKeys::TRANSACTION_ID, '');
        $wcOrder->update_meta_data(OrderMetaKeys::CREATION_TIME, (string) \time());
        $wcOrder->update_meta_data(OrderMetaKeys::THREE_D_SECURE_APPLIED_EXEMPTION, '');
        $wcOrder->update_meta_data(OrderMetaKeys::THREE_D_SECURE_LIABILITY, '');
        $wcOrder->update_meta_data(OrderMetaKeys::THREE_D_SECURE_AUTHENTICATION_STATUS, '');
        $wcOrder->save();
    }
}
