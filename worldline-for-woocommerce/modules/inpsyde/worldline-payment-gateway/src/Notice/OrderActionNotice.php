<?php

/**
 * Contains the messages to display, when capturing an authorization manually.
 *
 * @package WooCommerce\PayPalCommerce\WcGateway\Notice
 */
declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\Notice;

class OrderActionNotice
{
    public const QUERY_PARAM = 'worldline-action-message';
    public const CAPTURE_SUBMIT_ERROR = '1';
    public const CAPTURE_REQUIREMENTS_ERROR = '2';
    public function message() : ?string
    {
        $message = $this->currentMessage();
        if (\is_null($message)) {
            return null;
        }
        return '<div class="notice notice-' . \esc_html((string) $message['type']) . '">
                      <p>' . \esc_html((string) $message['message']) . '</p>
                </div>';
    }
    private function currentMessage() : ?array
    {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET[self::QUERY_PARAM]) || !\is_string($_GET[self::QUERY_PARAM])) {
            return null;
        }
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $errorMessageParameter = \sanitize_text_field(\wp_unslash($_GET[self::QUERY_PARAM]));
        if (\is_array($errorMessageParameter)) {
            return null;
        }
        $errorMessageCode = \sanitize_text_field($errorMessageParameter);
        if (empty($errorMessageCode)) {
            return null;
        }
        $messages = [self::CAPTURE_SUBMIT_ERROR => ['message' => \__('Failed to submit funds capture request. Please try again.', 'worldline-for-woocommerce'), 'type' => 'error'], self::CAPTURE_REQUIREMENTS_ERROR => ['message' => \__("This order doesn't meet the requirements to capture the funds.", 'worldline-for-woocommerce'), 'type' => 'error']];
        /**
         * @psalm-suppress InvalidArrayOffset
         */
        if (!isset($messages[$errorMessageCode])) {
            return null;
        }
        return $messages[$errorMessageCode];
    }
    public function displayMessage(string $messageCode) : void
    {
        $this->addWlopQueryParameter('woocommerce_redirect_order_location', $messageCode);
        $this->addWlopQueryParameter('redirect_post_location', $messageCode);
    }
    public function addWlopQueryParameter(string $filter, string $messageCode) : void
    {
        \add_filter($filter, static function (string $location) use($messageCode) {
            return \add_query_arg(self::QUERY_PARAM, $messageCode, $location);
        });
    }
}
