<?php

/*
 * This class was auto-generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedCheckout\HostedCheckoutClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedTokenization\HostedTokenizationClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Mandates\MandatesClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\PaymentLinks\PaymentLinksClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payments\PaymentsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payouts\PayoutsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\ProductGroups\ProductGroupsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Products\ProductsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Services\ServicesClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Sessions\SessionsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokens\TokensClient;
class MerchantClient extends ApiResource implements MerchantClientInterface
{
    /**
     * {@inheritDoc}
     */
    public function hostedCheckout()
    {
        return new HostedCheckoutClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function hostedTokenization()
    {
        return new HostedTokenizationClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function mandates()
    {
        return new MandatesClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function paymentLinks()
    {
        return new PaymentLinksClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function payments()
    {
        return new PaymentsClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function payouts()
    {
        return new PayoutsClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function productGroups()
    {
        return new ProductGroupsClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function products()
    {
        return new ProductsClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function services()
    {
        return new ServicesClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function sessions()
    {
        return new SessionsClient($this, $this->context);
    }
    /**
     * {@inheritDoc}
     */
    public function tokens()
    {
        return new TokensClient($this, $this->context);
    }
}
