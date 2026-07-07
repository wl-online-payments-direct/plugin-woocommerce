<?php

/*
 * This file was automatically generated.
 */
namespace Worldline\Vendor\OnlinePayments\Sdk\Merchant;

use Worldline\Vendor\OnlinePayments\Sdk\ApiResource;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Captures\CapturesClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Captures\CapturesClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\CofSeries\CofSeriesClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\CofSeries\CofSeriesClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Complete\CompleteClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Complete\CompleteClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedCheckout\HostedCheckoutClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedCheckout\HostedCheckoutClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedFields\HostedFieldsClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedFields\HostedFieldsClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedTokenization\HostedTokenizationClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\HostedTokenization\HostedTokenizationClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Mandates\MandatesClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Mandates\MandatesClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantBatch\MerchantBatchClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantBatch\MerchantBatchClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\PaymentLinks\PaymentLinksClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\PaymentLinks\PaymentLinksClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Payments\PaymentsClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Payments\PaymentsClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Payouts\PayoutsClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Payouts\PayoutsClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\PrivacyPolicy\PrivacyPolicyClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\PrivacyPolicy\PrivacyPolicyClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\ProductGroups\ProductGroupsClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\ProductGroups\ProductGroupsClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Products\ProductsClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Products\ProductsClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Refunds\RefundsClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Refunds\RefundsClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Services\ServicesClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Services\ServicesClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Sessions\SessionsClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Sessions\SessionsClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Subsequent\SubsequentClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Subsequent\SubsequentClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Tokenization\TokenizationClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Tokenization\TokenizationClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Tokens\TokensClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Tokens\TokensClientInterface;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Webhooks\WebhooksClient;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\Webhooks\WebhooksClientInterface;
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
