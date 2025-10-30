<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Helper\MoneyAmountConverter;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Notice\OrderActionNotice;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\PaymentCaptureValidator;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\AmountOfMoney;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CapturePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use WC_Order;
class AuthorizedPaymentProcessor
{
    private MerchantClientInterface $apiClient;
    private PaymentCaptureValidator $paymentCaptureValidator;
    private MoneyAmountConverter $moneyAmountConverter;
    private OrderActionNotice $orderActionNotice;
    public function __construct(MerchantClientInterface $apiClient, PaymentCaptureValidator $paymentCaptureValidator, MoneyAmountConverter $moneyAmountConverter, OrderActionNotice $orderActionNotice)
    {
        $this->apiClient = $apiClient;
        $this->paymentCaptureValidator = $paymentCaptureValidator;
        $this->moneyAmountConverter = $moneyAmountConverter;
        $this->orderActionNotice = $orderActionNotice;
    }
    /**
     * @throws \Exception
     */
    public function captureAuthorizedPayment(WC_Order $wcOrder) : void
    {
        $wlopWcOrder = new WlopWcOrder($wcOrder);
        if (!$this->paymentCaptureValidator->validate($wcOrder)) {
            $this->orderActionNotice->displayMessage((string) $this->orderActionNotice::CAPTURE_REQUIREMENTS_ERROR);
            return;
        }
        $transactionId = $wcOrder->get_meta(OrderMetaKeys::TRANSACTION_ID);
        $capturePaymentRequest = new CapturePaymentRequest();
        try {
            $captureAmount = $this->captureAmount((string) $transactionId);
            $capturePaymentRequest->setAmount($captureAmount->getAmount());
            $this->apiClient->payments()->capturePayment((string) $transactionId, $capturePaymentRequest);
            $wcOrder->update_meta_data(OrderMetaKeys::MANUAL_CAPTURE_SENT, 'yes');
            $wlopWcOrder->addWorldlineOrderNote(\__('Your fund capture request is submitted. You will receive a notification in the order notes upon completion.', 'worldline-for-woocommerce'));
            $wcOrder->save();
        } catch (\Throwable $exception) {
            $this->orderActionNotice->displayMessage((string) $this->orderActionNotice::CAPTURE_SUBMIT_ERROR);
            \do_action('wlop.admin_capture_error', ['exception' => $exception]);
        }
    }
    /**
     * @throws \Exception
     */
    protected function captureAmount(string $transactionId) : AmountOfMoney
    {
        $paymentDetails = $this->apiClient->payments()->getPaymentDetails($transactionId);
        return $paymentDetails->getPaymentOutput()->getAcquiredAmount();
    }
}
