<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\AbstractHostedPaymentRequestModifier;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\Order;
use WC_Order;
class HostedCheckoutInput
{
    private Order $order;
    private WC_Order $wcOrder;
    private string $returnUrl;
    private ?string $language;
    private ?string $token;
    private ?AbstractHostedPaymentRequestModifier $modifier;
    public function __construct(Order $order, WC_Order $wcOrder, string $returnUrl, ?string $language, ?string $token, ?AbstractHostedPaymentRequestModifier $modifier)
    {
        $this->order = $order;
        $this->wcOrder = $wcOrder;
        $this->returnUrl = $returnUrl;
        $this->language = $language;
        $this->token = $token;
        $this->modifier = $modifier;
    }
    public function order() : Order
    {
        return $this->order;
    }
    public function wcOrder() : WC_Order
    {
        return $this->wcOrder;
    }
    public function returnUrl() : string
    {
        return $this->returnUrl;
    }
    public function language() : ?string
    {
        return $this->language;
    }
    public function token() : ?string
    {
        return $this->token;
    }
    public function hostedCheckoutRequestModifier() : ?AbstractHostedPaymentRequestModifier
    {
        return $this->modifier;
    }
}
