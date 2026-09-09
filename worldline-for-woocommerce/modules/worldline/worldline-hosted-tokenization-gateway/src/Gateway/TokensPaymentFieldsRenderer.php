<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\HostedTokenizationGateway\Gateway;

use Worldline\Vendor\Worldline\PaymentGateway\PaymentFieldsRendererInterface;
use WC_Payment_Gateway;
class TokensPaymentFieldsRenderer implements PaymentFieldsRendererInterface
{
    protected WC_Payment_Gateway $gateway;
    protected bool $savedCardsEnabled;
    public function __construct(WC_Payment_Gateway $gateway, bool $savedCardsEnabled)
    {
        $this->gateway = $gateway;
        $this->savedCardsEnabled = $savedCardsEnabled;
    }
    public function renderFields() : string
    {
        \ob_start();
        if ($this->savedCardsEnabled) {
            $this->gateway->tokenization_script();
            $this->gateway->saved_payment_methods();
        }
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $this->gateway->get_description();
        return (string) \ob_get_clean();
    }
}
