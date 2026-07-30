<?php

declare (strict_types=1);
namespace Worldline\Vendor;

/**
 * Upgrade script for version 2.5.22
 *
 * This script is automatically executed when the plugin version is updated.
 *
 * The "Automatic cancellation" setting has been repurposed into the new
 * "Session timeout" setting. Both store the value in hours, but the timeout
 * can no longer be disabled and the 2-day / 3-day options have been removed
 * (the Worldline sessionTimeout API allows at most 24 hours).
 *
 * This migrates each merchant's previously stored value (in hours) into the
 * new `session_timeout` key:
 *   - Disabled (0)   -> 3 hours (the new default)
 *   - 2 days (48)    -> 24 hours (the new maximum)
 *   - 3 days (72)    -> 24 hours (the new maximum)
 *   - 1-24 hours     -> kept as-is
 *
 * @since 2.5.22
 */
if (!\defined('ABSPATH')) {
    exit;
}
try {
    $settingsKeys = ['woocommerce_worldline-for-woocommerce_settings', 'woocommerce_cawl-for-woocommerce_settings'];
    foreach ($settingsKeys as $settingsKey) {
        $settings = \get_option($settingsKey);
        if (!\is_array($settings) || !\array_key_exists('automatic_cancellation_hours', $settings)) {
            continue;
        }
        if (!isset($settings['session_timeout'])) {
            $hours = (int) $settings['automatic_cancellation_hours'];
            $settings['session_timeout'] = $hours <= 0 ? 3 : \min(24, $hours);
        }
        unset($settings['automatic_cancellation_hours']);
        \update_option($settingsKey, $settings);
        \error_log('Migrated automatic_cancellation_hours to session_timeout for ' . $settingsKey . '.');
    }
} catch (\Throwable $e) {
    \error_log('Migration failed: ' . $e->getMessage());
}
