<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Config\Sanitizer;

use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Config\CancellationIntervals;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentGateway;
use Worldline\Vendor\Worldline\PaymentGateway\SettingsFieldSanitizerInterface;
use RangeException;
/**
 * Enforces the accepted range of the custom session timeout, in minutes.
 *
 * The field also carries `min`/`max` HTML attributes, but those only guard the
 * browser - a raw POST bypasses them entirely. The bound has to hold here to
 * mean anything, otherwise an out-of-range value reaches
 * `hostedCheckoutSpecificInput.sessionTimeout`, which the Worldline SDK accepts
 * without any validation of its own.
 *
 * The strict check applies only when the dropdown is actually set to "Custom".
 * With a preset selected the field is hidden, yet WooCommerce still posts it, so
 * rejecting an empty or stale value there would abort a legitimate save over a
 * field the merchant cannot even see. In that case the value is normalised
 * instead: kept when usable, otherwise reset to the default.
 *
 * The dropdown is read from the raw POST rather than from the gateway's stored
 * settings, because sibling fields are sanitised in an undefined order and the
 * stored value may still be the pre-save one. Outside a settings POST (a
 * programmatic `update_option`, for instance) the lenient branch applies; the
 * value is clamped again when it is read, so the API is protected regardless.
 */
class SessionTimeoutSanitizer implements SettingsFieldSanitizerInterface
{
    protected const DROPDOWN_FIELD = 'session_timeout';
    protected string $errorMessage;
    public function __construct()
    {
        /** @psalm-suppress PossiblyFalsePropertyAssignmentValue */
        $this->errorMessage = \sprintf(\__('The session timeout value must be a whole number of minutes between %1$d and %2$d.', 'worldline-for-woocommerce'), CancellationIntervals::MIN_CUSTOM_MINUTES, CancellationIntervals::MAX_CUSTOM_MINUTES);
    }
    public function sanitize(string $key, $value, PaymentGateway $gateway)
    {
        $minutes = $this->parseMinutes($value);
        if (!$this->customTimeoutSelected($gateway)) {
            return (string) ($minutes ?? CancellationIntervals::DEFAULT_CUSTOM_MINUTES);
        }
        if ($minutes === null) {
            throw new RangeException($this->errorMessage);
        }
        return (string) $minutes;
    }
    /**
     * Whether the submitted form has "Custom" selected in the Session timeout dropdown.
     */
    protected function customTimeoutSelected(PaymentGateway $gateway) : bool
    {
        $fieldKey = $gateway->get_field_key(self::DROPDOWN_FIELD);
        if (!isset($_POST[$fieldKey]) || !\is_scalar($_POST[$fieldKey])) {
            return \false;
        }
        $selected = (string) \sanitize_text_field(\wp_unslash($_POST[$fieldKey]));
        return $selected === CancellationIntervals::CUSTOM;
    }
    /**
     * @param mixed $value
     * @return int|null Whole minutes within the accepted range, or null when unusable.
     */
    protected function parseMinutes($value) : ?int
    {
        $raw = \is_string($value) ? \trim($value) : $value;
        if (!\is_numeric($raw)) {
            return null;
        }
        $minutes = (int) $raw;
        if ((float) $minutes !== (float) $raw) {
            return null;
        }
        if ($minutes < CancellationIntervals::MIN_CUSTOM_MINUTES) {
            return null;
        }
        if ($minutes > CancellationIntervals::MAX_CUSTOM_MINUTES) {
            return null;
        }
        return $minutes;
    }
}
