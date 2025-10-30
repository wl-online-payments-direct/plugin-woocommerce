<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Refund;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use WC_Order;
class RefundValidator
{
    /**
     * @throws Exception
     */
    public function canRefund(WC_Order $wcOrder) : bool
    {
        $statusCode = (int) $wcOrder->get_meta(OrderMetaKeys::TRANSACTION_STATUS_CODE);
        return \in_array($statusCode, [9], \true) || $this->canCancelAuthorization($wcOrder);
    }
    /**
     * @throws Exception
     */
    public function canCancelAuthorization(WC_Order $wcOrder) : bool
    {
        $statusCode = (int) $wcOrder->get_meta(OrderMetaKeys::TRANSACTION_STATUS_CODE);
        return \in_array($statusCode, [5, 56], \true);
    }
    public function isWlopPaymentMethod(WC_Order $wcOrder) : bool
    {
        return \in_array($wcOrder->get_payment_method(), GatewayIds::ALL, \true);
    }
}
