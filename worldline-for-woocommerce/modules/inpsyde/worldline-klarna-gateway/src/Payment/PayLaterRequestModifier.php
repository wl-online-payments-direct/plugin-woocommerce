<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\KlarnaGateway\Payment;

class PayLaterRequestModifier extends AbstractKlarnaRequestModifier
{
    public function klarnaPaymentProductId(): int
    {
        return 3302;
    }
}
