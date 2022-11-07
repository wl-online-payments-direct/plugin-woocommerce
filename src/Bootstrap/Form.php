<?php

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
    const LOG_LEVEL = 'MoptWorldline.config.logLevel';
    const AUTO_CAPTURE = 'MoptWorldline.config.autoCapture';
    const AUTO_CAPTURE_DISABLED = 'disabled';
    const AUTO_CAPTURE_IMMEDIATELY = '0_day';
    const AUTO_CAPTURE_1_DAY = '1_day';
    const AUTO_CAPTURE_2_DAYS = '2_days';
    const AUTO_CAPTURE_3_DAYS = '3_days';
    const AUTO_CAPTURE_4_DAYS = '4_days';
    const AUTO_CAPTURE_5_DAYS = '5_days';

    /** @var string Fieldset name for the plugin custom field */
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_FIELDSET = 'payment_transaction_fieldset';

    /** @var string Field name for the plugin custom field */
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_HOSTED_CHECKOUT_ID = 'payment_transaction_id';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_STATUS = 'payment_transaction_status';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_TRANSACTION_READABLE_STATUS = 'payment_transaction_readable_status';
    const CUSTOM_FIELD_WORLDLINE_PAYMENT_METHOD_ID = 'worldline_payment_method_id';


    /** @var string Field name for the plugin session key */
    const SESSION_OPERATIONS_LOCK = 'order_locked';
}
