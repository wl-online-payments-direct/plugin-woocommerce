<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Fee;

use WC_Order_Item_Fee;
class FeeFactory
{
    public const CREDIT_CARD_SURCHARGE_META_KEY = 'wlop_credit_card_surcharge';
    public function create(string $feeName, float $feeValue) : WC_Order_Item_Fee
    {
        $fee = new WC_Order_Item_Fee();
        $fee->set_name($feeName);
        $fee->set_amount((string) $feeValue);
        $fee->set_tax_class('');
        $fee->set_tax_status('none');
        $fee->set_total((string) $feeValue);
        $fee->add_meta_data(FeeFactory::CREDIT_CARD_SURCHARGE_META_KEY, '1');
        return $fee;
    }
}
