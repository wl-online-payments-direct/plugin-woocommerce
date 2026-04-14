<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Capture;

use Exception;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\CaptureProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AmountOfMoneyFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use WC_Order;
class CaptureProcessor implements CaptureProcessorInterface
{
    private MerchantClientInterface $apiClient;
    private AmountOfMoneyFactory $amountOfMoneyFactory;
    private CaptureValidator $captureValidator;
    public function __construct(MerchantClientInterface $apiClient, AmountOfMoneyFactory $amountOfMoneyFactory, CaptureValidator $captureValidator)
    {
        $this->apiClient = $apiClient;
        $this->amountOfMoneyFactory = $amountOfMoneyFactory;
        $this->captureValidator = $captureValidator;
    }
    /**
     * Validates the order and submits a capture request to Worldline.
     *
     * @throws Exception
     */
    public function captureOrderAuthorization(WC_Order $wcOrder, float $amount, bool $isFinal) : void
    {
        if (!$this->captureValidator->isWlopPaymentMethod($wcOrder)) {
            return;
        }
        if (!$this->captureValidator->canCaptureAuthorization($wcOrder)) {
            throw new Exception(\__("This order doesn't meet the requirements to capture authorization.", 'worldline-for-woocommerce'));
        }
        $transactionId = (string) $wcOrder->get_meta(OrderMetaKeys::TRANSACTION_ID);
        $currency = $wcOrder->get_currency();
        $amountOfMoney = $this->amountOfMoneyFactory->create(new WcPriceStruct((string) $amount, $currency));
        $availableCents = $this->captureValidator->availableCents($wcOrder);
        $requestedCents = (int) \round($amount * 100);
        $this->captureValidator->validateAmountCents($requestedCents, $availableCents);
        $isFinal = $requestedCents === $availableCents;
        try {
            $this->handleCapture($transactionId, $amountOfMoney, $isFinal);
        } catch (\Throwable $exception) {
            \do_action('wlop.admin_capture_error', ['exception' => $exception]);
            throw new Exception(\__('Failed to submit a capture request. Please try again.', 'worldline-for-woocommerce'));
        }
        $wlopWcOrder = new WlopWcOrder($wcOrder);
        $note = \sprintf(\__('Your capture request for %s has been submitted.', 'worldline-for-woocommerce'), \html_entity_decode(\wp_strip_all_tags(\wc_price($amount))));
        $wlopWcOrder->addWorldlineOrderNote($note);
    }
    /**
     * Builds and sends the capture request to the Worldline API.
     *
     * @throws Exception
     */
    protected function handleCapture(string $transactionId, AmountOfMoney $amountOfMoney, bool $isFinal) : void
    {
        $captureRequest = new CapturePaymentRequest();
        $captureRequest->setAmount((int) $amountOfMoney->getAmount());
        $captureRequest->setIsFinal($isFinal);
        $this->apiClient->payments()->capturePayment($transactionId, $captureRequest);
    }
}
