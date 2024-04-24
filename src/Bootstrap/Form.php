<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Bootstrap
 */

namespace MoptWorldline\Bootstrap;

/**
 * This class will represent the plugin config options
 *
 * @author Mediaopt GmbH
 * @package MoptWorldline\Bootstrap
 */
class Form
{
    /** Field names for the plugin config */
    const IS_LIVE_MODE_FIELD = 'MoptWorldline.config.isLiveMode';
    const LIVE_ENDPOINT_FIELD = 'MoptWorldline.config.liveEndpoint';
    const SANDBOX_ENDPOINT_FIELD = 'MoptWorldline.config.sandboxEndpoint';
    const RETURN_URL_FIELD = 'MoptWorldline.config.returnUrl';
    const MERCHANT_ID_FIELD  = 'MoptWorldline.config.merchantId';
    const API_KEY_FIELD  = 'MoptWorldline.config.apiKey';
    const API_SECRET_FIELD = 'MoptWorldline.config.apiSecret';
    const WEBHOOK_KEY_FIELD  = 'MoptWorldline.config.webhookKey';
    const WEBHOOK_SECRET_FIELD = 'MoptWorldline.config.webhookSecret';
    const LIVE_MERCHANT_ID_FIELD  = 'MoptWorldline.config.liveMerchantId';
    const LIVE_API_KEY_FIELD  = 'MoptWorldline.config.liveApiKey';
    const LIVE_API_SECRET_FIELD = 'MoptWorldline.config.liveApiSecret';
    const LIVE_WEBHOOK_KEY_FIELD  = 'MoptWorldline.config.liveWebhookKey';
    const LIVE_WEBHOOK_SECRET_FIELD = 'MoptWorldline.config.liveWebhookSecret';
    const LIVE_RETURN_URL_FIELD = 'MoptWorldline.config.liveReturnUrl';
    const LIVE_API_TEST_BUTTON = 'MoptWorldline.config.liveApiTest';
    const LOG_LEVEL = 'MoptWorldline.config.logLevel';
    const IFRAME_TEMPLATE_NAME = 'MoptWorldline.config.iframeTemplateName';
    const ONEY_PAYMENT_OPTION_FIELD = 'MoptWorldline.config.oneyPaymentOption';
    const FULL_REDIRECT_TEMPLATE_NAME = 'MoptWorldline.config.fullRedirectTemplateName';
    const AUTO_CAPTURE = 'MoptWorldline.config.autoCapture';
    const AUTO_CANCEL = 'MoptWorldline.config.autoCancel';
    const AUTO_PROCESSING_DISABLED = 'disabled';
    const AUTO_CAPTURE_IMMEDIATELY = '0_day';
    const AUTO_CAPTURE_1_DAY = '1_day';
    const AUTO_CAPTURE_2_DAYS = '2_days';
    const AUTO_CAPTURE_3_DAYS = '3_days';
    const AUTO_CAPTURE_4_DAYS = '4_days';
    const AUTO_CAPTURE_5_DAYS = '5_days';
    const GROUP_CARDS = 'MoptWorldline.config.groupCards';
    const PARTIAL_OPERATIONS_ENABLED = 'MoptWorldline.config.partialOperationsEnabled';

    /** @var string Fieldset name for the plugin custom field */
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_FIELDSET = 'payment_transaction_fieldset';

    /** @var string Field name for the plugin custom field */
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID = 'payment_transaction_id';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS = 'payment_transaction_status';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_CAPTURE_AMOUNT = 'payment_transaction_capture_amount';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_REFUND_AMOUNT = 'payment_transaction_refund_amount';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_LOG = 'payment_transaction_log';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_ITEMS_STATUS = 'payment_transaction_items';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_READABLE_STATUS = 'payment_transaction_readable_status';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_IS_LOCKED = 'payment_transaction_locked';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID = 'worldline_payment_method_id';
    const CUSTOM_FIELD_WORLDLINE_CUSTOMER_SAVED_PAYMENT_CARD_TOKEN = 'worldline_saved_payment_card_token';
    const CUSTOM_FIELD_WORLDLINE_CUSTOMER_ACCOUNT_PAYMENT_CARD_TOKEN = 'worldline_account_payment_card_token';

    /** @var string Field name for the cart form */
    const WORLDLINE_CART_FORM_HOSTED_TOKENIZATION_ID = 'moptWorldlineHostedTokenizationId';
    const WORLDLINE_CART_FORM_BROWSER_DATA_COLOR_DEPTH = 'moptWorldlineBrowserDataColorDepth';
    const WORLDLINE_CART_FORM_BROWSER_DATA_JAVA_ENABLED = 'moptWorldlineBrowserDataJavaEnabled';
    const WORLDLINE_CART_FORM_LOCALE = 'moptWorldlineLocale';
    const WORLDLINE_CART_FORM_BROWSER_DATA_SCREEN_HEIGHT = 'moptWorldlineBrowserDataScreenHeight';
    const WORLDLINE_CART_FORM_BROWSER_DATA_SCREEN_WIDTH = 'moptWorldlineBrowserDataScreenWidth';
    const WORLDLINE_CART_FORM_TIMEZONE_OFFSET_MINUTES = 'moptWorldlineTimezoneOffsetUtcMinutes';
    const WORLDLINE_CART_FORM_USER_AGENT = 'moptWorldlineUserAgent';

    const WORLDLINE_CART_FORM_KEYS = [
        self::WORLDLINE_CART_FORM_HOSTED_TOKENIZATION_ID,
        self::WORLDLINE_CART_FORM_BROWSER_DATA_COLOR_DEPTH,
        self::WORLDLINE_CART_FORM_BROWSER_DATA_JAVA_ENABLED,
        self::WORLDLINE_CART_FORM_LOCALE,
        self::WORLDLINE_CART_FORM_BROWSER_DATA_SCREEN_HEIGHT,
        self::WORLDLINE_CART_FORM_BROWSER_DATA_SCREEN_WIDTH,
        self::WORLDLINE_CART_FORM_TIMEZONE_OFFSET_MINUTES,
        self::WORLDLINE_CART_FORM_USER_AGENT,
    ];

    const WORLDLINE_CART_FORM_REDIRECT_TOKEN = 'moptWorldlineRedirectToken';

    /** @var string Field name for the plugin session key */
    const SESSION_OPERATIONS_LOCK = 'order_locked';
    const SESSION_IFRAME_DATA = 'worldline_iframe_data';
}
