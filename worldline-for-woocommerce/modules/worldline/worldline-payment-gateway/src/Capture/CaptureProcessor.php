<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Capture;

use Exception;
use Worldline\Vendor\Worldline\PaymentGateway\CaptureProcessorInterface;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\AmountOfMoneyFactory;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\Struct\WcPriceStruct;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Worldline\Vendor\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Worldline\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
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
