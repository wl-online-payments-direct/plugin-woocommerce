<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

/**
 * Upgrade script for version 2.4.7
 *
 * This script is automatically executed when plugin version is updated.
 * It migrates CAWL plugin settings from the old option key to the new one.
 *
 * @since 2.4.7
 */
if (!\defined('ABSPATH')) {
    exit;
}
try {
    $old_key = 'woocommerce_woocommerce-for-cawl_settings';
    $new_key = 'woocommerce_cawl-for-woocommerce_settings';
    $old_value = \get_option($old_key);
    $new_value = \get_option($new_key);
    if ($old_value) {
        \update_option($new_key, $old_value);
        \error_log('[CAWL UPGRADE 2.4.7] Settings migrated successfully from old key to new key.');
    } else {
        \error_log('[CAWL UPGRADE 2.4.7] ⚠️ No old settings found, nothing to migrate.');
    }
} catch (\Throwable $e) {
    \error_log('[CAWL UPGRADE 2.4.7] Migration failed: ' . $e->getMessage());
}
