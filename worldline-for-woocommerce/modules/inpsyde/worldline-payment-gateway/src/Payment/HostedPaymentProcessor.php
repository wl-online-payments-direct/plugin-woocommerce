<?php

declare (strict_types=1);
// phpcs:disable WordPress.Security.NonceVerification
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Payment;

use Exception;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentGateway;
use Syde\Vendor\Inpsyde\PaymentGateway\PaymentProcessorInterface;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Vaulting\WcTokenRepository;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutInput;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\HostedCheckoutUrlFactory;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Api\WcOrderBasedOrderFactoryInterface;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\OrderMetaKeys;
use Syde\Vendor\OnlinePayments\Sdk\Domain\APIError;
use Syde\Vendor\OnlinePayments\Sdk\Domain\ErrorResponse;
use Syde\Vendor\OnlinePayments\Sdk\ValidationException;
use Throwable;
use WC_Order;
class HostedPaymentProcessor implements PaymentProcessorInterface
{
    private HostedCheckoutUrlFactory $hostedCheckoutUrlFactory;
    private WcOrderBasedOrderFactoryInterface $wcOrderBasedFactory;
    private WcTokenRepository $wcTokenRepository;
    private ?string $hostedCheckoutLanguage;
    public function __construct(HostedCheckoutUrlFactory $hostedCheckoutUrlFactory, WcOrderBasedOrderFactoryInterface $wcOrderBasedOrderFactory, WcTokenRepository $wcTokenRepository, ?string $hostedCheckoutLanguage)
    {
        $this->hostedCheckoutUrlFactory = $hostedCheckoutUrlFactory;
        $this->wcOrderBasedFactory = $wcOrderBasedOrderFactory;
        $this->wcTokenRepository = $wcTokenRepository;
        $this->hostedCheckoutLanguage = $hostedCheckoutLanguage;
    }
    /**
     * @throws Throwable
     */
    public function processPayment(WC_Order $wcOrder, PaymentGateway $gateway): array
    {
        try {
            $token = null;
            $tokenId = $this->tokenId();
            if (!is_null($tokenId)) {
                $wcToken = $this->wcTokenRepository->get($tokenId);
                if (!$wcToken || $wcToken->get_user_id() !== get_current_user_id() || $wcToken->get_gateway_id() !== $gateway->id) {
                    throw new Exception('Invalid saved token.');
                }
                $token = $wcToken->get_token();
            }
            $wlopOrder = $this->wcOrderBasedFactory->create($wcOrder);
            $this->initWlopWcOrder($wcOrder);
            $hostedCheckoutResponse = $this->hostedCheckoutUrlFactory->create(new HostedCheckoutInput($wlopOrder, $wcOrder->get_checkout_order_received_url(), $this->hostedCheckoutLanguage, $token));
            $wcOrder->add_meta_data(OrderMetaKeys::HOSTED_CHECKOUT_ID, $hostedCheckoutResponse->getHostedCheckoutId());
            $wcOrder->save();
        } catch (Throwable $exception) {
            $errors = '';
            if ($exception instanceof ValidationException) {
                $errors = $this->extractErrors($exception);
            }
            do_action('wlop.hosted_payment_error', ['exception' => $exception, 'errors' => $errors]);
            wc_add_notice(__('Failed to process checkout. Please try again or contact the shop admin.', 'worldline-for-woocommerce'), 'error');
            return ['result' => 'failure'];
        }
        return ['result' => 'success', 'redirect' => $hostedCheckoutResponse->getRedirectUrl()];
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
    protected function tokenId(): ?int
    {
        foreach (['wlop_token', 'token'] as $tokenKey) {
            if (isset($_POST[$tokenKey]) && is_numeric($_POST[$tokenKey])) {
                return (int) $_POST[$tokenKey];
            }
        }
        return null;
    }
}
