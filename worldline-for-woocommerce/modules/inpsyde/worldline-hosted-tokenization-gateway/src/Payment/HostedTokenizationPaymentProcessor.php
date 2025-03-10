<?php

declare (strict_types=1);
// phpcs:disable WordPress.Security.NonceVerification
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway\Payment;

use Syde\Vendor\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentProcessorInterface;
use Syde\Vendor\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\ThreeDSecureFactory;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\OnlinePayments\Sdk\Domain\APIError;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use Syde\Vendor\OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use Syde\Vendor\OnlinePayments\Sdk\Domain\ErrorResponse;
use Syde\Vendor\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
use Throwable;
use WC_Order;
class HostedTokenizationPaymentProcessor implements PaymentProcessorInterface
{
    private HostedPaymentProcessor $hostedPaymentProcessor;
    private WcOrderBasedOrderFactoryInterface $wcOrderBasedFactory;
    private Transformer $requestTransformer;
    private MerchantClientInterface $client;
    private string $authorizationMode;
    private ThreeDSecureFactory $threedSecureFactory;
    public function __construct(HostedPaymentProcessor $hostedPaymentProcessor, WcOrderBasedOrderFactoryInterface $wcOrderBasedFactory, Transformer $requestTransformer, MerchantClientInterface $client, string $authorizationMode, ThreeDSecureFactory $threedSecureFactory)
    {
        $this->hostedPaymentProcessor = $hostedPaymentProcessor;
        $this->wcOrderBasedFactory = $wcOrderBasedFactory;
        $this->requestTransformer = $requestTransformer;
        $this->client = $client;
        $this->authorizationMode = $authorizationMode;
        $this->threedSecureFactory = $threedSecureFactory;
    }
    /**
     * @throws Throwable
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     */
    public function processPayment(WC_Order $wcOrder, PaymentGateway $gateway): array
    {
        $wcOrder->set_status('pending');
        $wcOrder->save();
        $hostedTokenizationId = $this->hostedTokenizationId();
        if ($hostedTokenizationId === null) {
            // Fallback to redirect, e.g. when no JavaScript.
            do_action('wlop.hosted_tokenization_fallback');
            return $this->hostedPaymentProcessor->processPayment($wcOrder, $gateway);
        }
        try {
            $wlopOrder = $this->wcOrderBasedFactory->create($wcOrder);
            $this->initWlopWcOrder($wcOrder);
            $paymentRequest = new CreatePaymentRequest();
            $paymentRequest->setHostedTokenizationId($hostedTokenizationId);
            $cardPaymentMethodSpecificInput = $this->requestTransformer->create(CardPaymentMethodSpecificInput::class, new HostedCheckoutInput($wlopOrder, $wcOrder, '', null, null, null));
            assert($cardPaymentMethodSpecificInput instanceof CardPaymentMethodSpecificInput);
            $threedSecure = $this->threedSecureFactory->create($wlopOrder->getAmountOfMoney()->getAmount(), $wlopOrder->getAmountOfMoney()->getCurrencyCode(), $wcOrder->get_checkout_order_received_url());
            $cardPaymentMethodSpecificInput->setThreeDSecure($threedSecure);
            $paymentRequest->setOrder($wlopOrder);
            $paymentRequest->setCardPaymentMethodSpecificInput($cardPaymentMethodSpecificInput);
            $response = $this->client->payments()->createPayment($paymentRequest);
            $transactionId = $response->getPayment()->getId();
            if (!empty($transactionId)) {
                $wlopWcOrder = new WlopWcOrder($wcOrder);
                $wlopWcOrder->setTransactionId($transactionId);
            }
            $merchantAction = $response->getMerchantAction();
            if ($merchantAction && $merchantAction->getActionType() === 'REDIRECT') {
                return ['result' => 'success', 'redirect' => $merchantAction->getRedirectData()->getRedirectURL()];
            }
        } catch (Throwable $exception) {
            $errors = '';
            if ($exception instanceof ValidationException) {
                $errors = $this->extractErrors($exception);
            }
            do_action('wlop.hosted_tokenization_payment_error', ['exception' => $exception, 'errors' => $errors]);
            wc_add_notice(__('Failed to process checkout. Please try again or contact the shop admin.', 'worldline-for-woocommerce'), 'error');
            return ['result' => 'failure'];
        }
        return ['result' => 'success', 'redirect' => $gateway->get_return_url($wcOrder)];
    }
    protected function hostedTokenizationId(): ?string
    {
        $key = 'wlop_hosted_tokenization_id';
        if (!isset($_POST[$key])) {
            return null;
        }
        /** @psalm-suppress PossiblyInvalidArgument */
        $hostedTokenizationId = sanitize_text_field(wp_unslash($_POST[$key]));
        if (empty($hostedTokenizationId)) {
            return null;
        }
        return $hostedTokenizationId;
    }
    protected function initWlopWcOrder(WC_Order $wcOrder): void
    {
        /** Some webhooks arrive almost at the same time. There is a high possibility
         * for 1st and 2nd webhook to create two separate meta fields with the same key,
         * because they are both unaware that the data is about to be saved. We
         * save empty values for these meta-fields, so that can't happen.
         */
        $wcOrder->add_meta_data(OrderMetaKeys::TRANSACTION_STATUS_CODE, '-1');
        $wcOrder->add_meta_data(OrderMetaKeys::TRANSACTION_ID, '');
        $wcOrder->save();
    }
    protected function extractErrors(ValidationException $exception): string
    {
        $response = $exception->getResponse();
        assert($response instanceof ErrorResponse);
        $errorMessages = array_map(static function (APIError $error): string {
            return $error->getMessage();
        }, $response->getErrors());
        return implode(', ', $errorMessages);
    }
}
