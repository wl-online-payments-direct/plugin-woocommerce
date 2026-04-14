<?php

/*
 * This file was automatically generated.
 */
namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant;

use Syde\Vendor\Worldline\OnlinePayments\Sdk\ApiResource;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Captures\CapturesClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Captures\CapturesClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\CofSeries\CofSeriesClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\CofSeries\CofSeriesClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Complete\CompleteClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Complete\CompleteClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedCheckout\HostedCheckoutClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedCheckout\HostedCheckoutClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedFields\HostedFieldsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedFields\HostedFieldsClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedTokenization\HostedTokenizationClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\HostedTokenization\HostedTokenizationClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Mandates\MandatesClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Mandates\MandatesClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantBatch\MerchantBatchClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantBatch\MerchantBatchClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\PaymentLinks\PaymentLinksClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\PaymentLinks\PaymentLinksClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payments\PaymentsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payments\PaymentsClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payouts\PayoutsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Payouts\PayoutsClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\PrivacyPolicy\PrivacyPolicyClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\PrivacyPolicy\PrivacyPolicyClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\ProductGroups\ProductGroupsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\ProductGroups\ProductGroupsClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Products\ProductsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Products\ProductsClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Refunds\RefundsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Refunds\RefundsClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Services\ServicesClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Services\ServicesClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Sessions\SessionsClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Sessions\SessionsClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Subsequent\SubsequentClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Subsequent\SubsequentClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokenization\TokenizationClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokenization\TokenizationClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokens\TokensClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Tokens\TokensClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Webhooks\WebhooksClient;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\Webhooks\WebhooksClientInterface;
/**
 * Merchant client.
 */
class MerchantClient extends ApiResource implements MerchantClientInterface
{
    /**
     * @inheritdoc
     */
    public function hostedCheckout() : HostedCheckoutClientInterface
    {
        return new HostedCheckoutClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function hostedTokenization() : HostedTokenizationClientInterface
    {
        return new HostedTokenizationClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function hostedFields() : HostedFieldsClientInterface
    {
        return new HostedFieldsClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function payments() : PaymentsClientInterface
    {
        return new PaymentsClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function captures() : CapturesClientInterface
    {
        return new CapturesClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function refunds() : RefundsClientInterface
    {
        return new RefundsClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function complete() : CompleteClientInterface
    {
        return new CompleteClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function subsequent() : SubsequentClientInterface
    {
        return new SubsequentClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function productGroups() : ProductGroupsClientInterface
    {
        return new ProductGroupsClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function products() : ProductsClientInterface
    {
        return new ProductsClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function services() : ServicesClientInterface
    {
        return new ServicesClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function webhooks() : WebhooksClientInterface
    {
        return new WebhooksClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function sessions() : SessionsClientInterface
    {
        return new SessionsClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function tokens() : TokensClientInterface
    {
        return new TokensClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function cofSeries() : CofSeriesClientInterface
    {
        return new CofSeriesClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function tokenization() : TokenizationClientInterface
    {
        return new TokenizationClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function payouts() : PayoutsClientInterface
    {
        return new PayoutsClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function mandates() : MandatesClientInterface
    {
        return new MandatesClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function privacyPolicy() : PrivacyPolicyClientInterface
    {
        return new PrivacyPolicyClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function paymentLinks() : PaymentLinksClientInterface
    {
        return new PaymentLinksClient($this, $this->context);
    }
    /**
     * @inheritdoc
     */
    public function merchantBatch() : MerchantBatchClientInterface
    {
        return new MerchantBatchClient($this, $this->context);
    }
}
