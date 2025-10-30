<?php

declare (strict_types=1);
// phpcs:disable WordPress.Security.NonceVerification
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\HostedTokenizationGateway\Payment;

use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\Transformer\Transformer;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\HostedPaymentProcessor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment\OrderInitTrait;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\ThreeDSecure\CardThreeDSecureFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\WlopWcOrder;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\APIError;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CardPaymentMethodSpecificInput;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\CreatePaymentRequest;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Domain\ErrorResponse;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\Merchant\MerchantClientInterface;
use Syde\Vendor\Worldline\OnlinePayments\Sdk\ValidationException;
use Throwable;
use WC_Order;
class HostedTokenizationPaymentProcessor implements PaymentProcessorInterface
{
    use OrderInitTrait;
    private HostedPaymentProcessor $hostedPaymentProcessor;
    private WcOrderBasedOrderFactoryInterface $wcOrderBasedFactory;
    private Transformer $requestTransformer;
    private MerchantClientInterface $client;
    private string $authorizationMode;
    private CardThreeDSecureFactory $cardThreedSecureFactory;
    public function __construct(HostedPaymentProcessor $hostedPaymentProcessor, WcOrderBasedOrderFactoryInterface $wcOrderBasedFactory, Transformer $requestTransformer, MerchantClientInterface $client, string $authorizationMode, CardThreeDSecureFactory $threedSecureFactory)
    {
        $this->hostedPaymentProcessor = $hostedPaymentProcessor;
        $this->wcOrderBasedFactory = $wcOrderBasedFactory;
        $this->requestTransformer = $requestTransformer;
        $this->client = $client;
        $this->authorizationMode = $authorizationMode;
        $this->cardThreedSecureFactory = $threedSecureFactory;
    }
    /**
     * @throws Throwable
     * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
     */
    public function processPayment(WC_Order $wcOrder, PaymentGateway $gateway) : array
    {
        $wcOrder->set_status('pending');
        $wcOrder->save();
        $hostedTokenizationId = $this->hostedTokenizationId();
        if ($hostedTokenizationId === null) {
            // Fallback to redirect, e.g. when no JavaScript.
            \do_action('wlop.hosted_tokenization_fallback');
            return $this->hostedPaymentProcessor->processPayment($wcOrder, $gateway);
        }
        try {
            $wlopOrder = $this->wcOrderBasedFactory->create($wcOrder);
            $this->initWlopWcOrder($wcOrder);
            $paymentRequest = new CreatePaymentRequest();
            $paymentRequest->setHostedTokenizationId($hostedTokenizationId);
            $cardPaymentMethodSpecificInput = $this->requestTransformer->create(CardPaymentMethodSpecificInput::class, new HostedCheckoutInput($wlopOrder, $wcOrder, '', null, null, null));
            \assert($cardPaymentMethodSpecificInput instanceof CardPaymentMethodSpecificInput);
            $cardPaymentMethodSpecificInput->setThreeDSecure($this->cardThreedSecureFactory->create($wlopOrder->getAmountOfMoney()->getAmount(), $wlopOrder->getAmountOfMoney()->getCurrencyCode(), $wcOrder->get_checkout_order_received_url()));
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
            \do_action('wlop.hosted_tokenization_payment_error', ['exception' => $exception, 'errors' => $errors]);
            \wc_add_notice(\__('Failed to process checkout. Please try again or contact the store admin.', 'worldline-for-woocommerce'), 'error');
            return ['result' => 'failure'];
        }
        return ['result' => 'success', 'redirect' => $gateway->get_return_url($wcOrder)];
    }
    protected function hostedTokenizationId() : ?string
    {
        $key = 'wlop_hosted_tokenization_id';
        if (!isset($_POST[$key])) {
            return null;
        }
        /** @psalm-suppress PossiblyInvalidArgument */
        $hostedTokenizationId = \sanitize_text_field(\wp_unslash($_POST[$key]));
        if (empty($hostedTokenizationId)) {
            return null;
        }
        return $hostedTokenizationId;
    }
    protected function extractErrors(ValidationException $exception) : string
    {
        $response = $exception->getResponse();
        \assert($response instanceof ErrorResponse);
        $errorMessages = \array_map(static function (APIError $error) : string {
            return $error->getMessage();
        }, $response->getErrors());
        return \implode(', ', $errorMessages);
    }
}
