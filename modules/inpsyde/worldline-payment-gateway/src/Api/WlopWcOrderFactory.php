<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\WebhooksEvent;
use WC_Order;
class WlopWcOrderFactory
{
    /**
     * @throws Exception
     */
    public function create(WebhooksEvent $webhook) : WlopWcOrder
    {
        $ref = WebhookHelper::reference($webhook);
        if ($ref === null) {
            throw new Exception('Merchant reference not found.');
        }
        $wcOrder = \wc_get_order((int) $ref);
        if (!$wcOrder instanceof WC_Order) {
            throw new Exception("Failed to find WC order for merchant reference {$ref}.");
        }
        return new WlopWcOrder($wcOrder);
    }
}
