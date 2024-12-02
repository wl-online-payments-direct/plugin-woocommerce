<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Handler;

use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Webhooks\Helper\WebhookHelper;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\OnlinePayments\Sdk\Domain\WebhooksEvent;
class PaymentCapturedHandler implements WebhookHandlerInterface
{
    private MoneyAmountConverter $moneyAmountConverter;
    public function __construct(MoneyAmountConverter $moneyAmountConverter)
    {
        $this->moneyAmountConverter = $moneyAmountConverter;
    }
    public function accepts(WebhooksEvent $webhook): bool
    {
        return $webhook->getType() === 'payment.captured';
    }
    /**
     * @throws \Exception
     */
    public function handle(WebhooksEvent $webhook, WlopWcOrder $wlopWcOrder): void
    {
        $capturedAmount = WebhookHelper::paymentCapturedAmount($webhook);
        if ($capturedAmount === null) {
            throw new \Exception("Can't retrieve captured amount. Webhook: {$webhook->getId()}");
        }
        $wlopWcOrder->addWorldlineOrderNote(sprintf(
            /* translators: %s refers to the capture amount */
            __('Payment of %s successfully captured.', 'worldline-for-woocommerce'),
            $this->moneyAmountConverter->amountOfMoneyAsString($capturedAmount)
        ));
        $wlopWcOrder->order()->save();
    }
}
