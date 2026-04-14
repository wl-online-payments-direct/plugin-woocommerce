<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\PaymentGateway;

use Exception;
use WC_Order;
interface CaptureProcessorInterface
{
    /**
     * @throws Exception If failed to capture authorization.
     */
    public function captureOrderAuthorization(WC_Order $wcOrder, float $amount, bool $isFinal) : void;
}
