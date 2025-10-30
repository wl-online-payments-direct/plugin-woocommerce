<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use WC_Order;
trait OrderInitTrait
{
    protected function initWlopWcOrder(WC_Order $wcOrder) : void
    {
        /** Some webhooks arrive almost at the same time. There is a high possibility
         * for 1st and 2nd webhook to create two separate meta fields with the same key,
         * because they are both unaware that the data is about to be saved. We
         * save empty values for these meta-fields, so that can't happen.
         */
        $wcOrder->add_meta_data(OrderMetaKeys::TRANSACTION_STATUS_CODE, '-1');
        $wcOrder->add_meta_data(OrderMetaKeys::TRANSACTION_ID, '');
        $wcOrder->add_meta_data(OrderMetaKeys::CREATION_TIME, (string) \time());
        $wcOrder->add_meta_data(OrderMetaKeys::THREE_D_SECURE_RESULT_PROCESSED, '');
        $wcOrder->add_meta_data(OrderMetaKeys::THREE_D_SECURE_LIABILITY, '');
        $wcOrder->add_meta_data(OrderMetaKeys::THREE_D_SECURE_LIABILITY, '');
        $wcOrder->save();
    }
}
