<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\PaymentGateway;

use Exception;
use WC_Order;
interface CancelProcessorInterface
{
    /**
     * @throws Exception If failed to cancel authorization.
     */
    public function cancelOrderAuthorization(WC_Order $wcOrder, float $amount, bool $isFinal) : void;
}
