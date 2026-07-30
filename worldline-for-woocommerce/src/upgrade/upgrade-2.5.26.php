<?php

declare (strict_types=1);
namespace Worldline\Vendor;

/**
 * Upgrade script for version 2.5.26
 *
 * This script is automatically executed when the plugin version is updated.
 *
 * PSPID used to be a single, environment-agnostic setting. It is now split into
 * environment-specific `test_pspid` / `live_pspid` values, matching the other
 * credentials, so switching environments no longer silently reuses the wrong
 * PSPID.
 *
 * The existing `pspid` is copied into every environment that has stored API
 * credentials (a non-empty API key AND API secret): a merchant with credentials
 * saved for an environment is actively using it, so the current PSPID almost
 * certainly applies there. Each side is force-written - set to the PSPID when
 * that environment has credentials, cleared to empty otherwise - so no stale
 * value survives on an environment that has none.
 *
 * If neither environment has stored credentials, the `live_mode` toggle decides
 * the active side so the value is not lost.
 *
 * @since 2.5.26
 */
if (!\defined('ABSPATH')) {
    exit;
}
try {
    $settingsKeys = ['woocommerce_worldline-for-woocommerce_settings', 'woocommerce_cawl-for-woocommerce_settings'];
    foreach ($settingsKeys as $settingsKey) {
        $settings = \get_option($settingsKey);
        if (!\is_array($settings) || !\array_key_exists('pspid', $settings)) {
            continue;
        }
        $pspid = (string) $settings['pspid'];
        $putOnTest = !empty($settings['test_api_key']) && !empty($settings['test_api_secret']);
        $putOnLive = !empty($settings['live_api_key']) && !empty($settings['live_api_secret']);
        if (!$putOnTest && !$putOnLive) {
            // No stored credentials in either environment - fall back to the
            // active environment (per the toggle) so the PSPID is preserved.
            if (($settings['live_mode'] ?? 'no') !== 'no') {
                $putOnLive = \true;
            } else {
                $putOnTest = \true;
            }
        }
        $settings['test_pspid'] = $putOnTest ? $pspid : '';
        $settings['live_pspid'] = $putOnLive ? $pspid : '';
        unset($settings['pspid']);
        \update_option($settingsKey, $settings);
        \error_log('Migrated pspid to test_pspid/live_pspid for ' . $settingsKey . '.');
    }
} catch (\Throwable $e) {
    \error_log('Migration failed: ' . $e->getMessage());
}
