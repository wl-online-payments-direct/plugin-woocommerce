<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Syde\Vendor\OnlinePayments\Sdk\Domain\Order;
class HostedCheckoutInput
{
    private Order $order;
    private string $returnUrl;
    private ?string $language;
    private ?string $token;
    public function __construct(Order $order, string $returnUrl, ?string $language, ?string $token)
    {
        $this->order = $order;
        $this->returnUrl = $returnUrl;
        $this->language = $language;
        $this->token = $token;
    }
    public function order(): Order
    {
        return $this->order;
    }
    public function returnUrl(): string
    {
        return $this->returnUrl;
    }
    public function language(): ?string
    {
        return $this->language;
    }
    public function token(): ?string
    {
        return $this->token;
    }
}
