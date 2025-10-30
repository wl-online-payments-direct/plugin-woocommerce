<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Order;
use WC_Order;
interface WcOrderBasedOrderFactoryInterface
{
    public function create(WC_Order $wcOrder) : Order;
}
