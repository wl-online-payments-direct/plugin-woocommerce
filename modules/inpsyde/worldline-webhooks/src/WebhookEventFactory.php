<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks;

use Exception;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Webhooks\InMemorySecretKeyStore;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Webhooks\SignatureValidationException;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Webhooks\WebhooksHelper;
class WebhookEventFactory
{
    private string $webhookId;
    private string $webhookSecretKey;
    public function __construct(string $webhookId, string $webhookSecretKey)
    {
        $this->webhookId = $webhookId;
        $this->webhookSecretKey = $webhookSecretKey;
    }
    /**
     * Verified the signature and return parsed webhook event object.
     *
     * @throws SignatureValidationException
     * @throws Exception
     */
    public function fromRequest(string $body, array $headers) : WebhooksEvent
    {
        $secretKeyStore = new InMemorySecretKeyStore([$this->webhookId => $this->webhookSecretKey]);
        $helper = new WebhooksHelper($secretKeyStore);
        $headersWithoutArrays = [];
        foreach ($headers as $key => $value) {
            $originalKey = \str_replace('_', '-', $key);
            \assert(\is_string($originalKey));
            $headersWithoutArrays[$originalKey] = \is_array($value) ? $value[0] : $value;
        }
        return $helper->unmarshal($body, $headersWithoutArrays);
    }
}
