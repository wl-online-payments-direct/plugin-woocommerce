<?php

declare (strict_types=1);
namespace Worldline\Vendor;

/**
 * Upgrade 2.5.23: rewrite stored module asset paths after the module-directory rename.
 *
 * @since 2.5.23
 */
if (!\defined('ABSPATH')) {
    exit;
}
try {
    global $wpdb;
    $pattern = '#modules/(?:inpsyde|worldline|cawl)/worldline-#';
    $replace = 'modules/worldline/worldline-';
    $optionNames = $wpdb->get_col($wpdb->prepare("SELECT option_name FROM {$wpdb->options} WHERE option_value LIKE %s", '%modules/%'));
    if (empty($optionNames)) {
        return;
    }
    $rewrite = static function ($value) use(&$rewrite, $pattern, $replace) {
        if (\is_string($value)) {
            return \preg_replace($pattern, $replace, $value);
        }
        if (\is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $rewrite($item);
            }
            return $value;
        }
        if (\is_object($value)) {
            foreach (\get_object_vars($value) as $key => $item) {
                $value->{$key} = $rewrite($item);
            }
            return $value;
        }
        return $value;
    };
    foreach ($optionNames as $optionName) {
        $current = \get_option($optionName);
        $updated = $rewrite($current);
        if ($updated !== $current) {
            \update_option($optionName, $updated);
        }
    }
    \error_log('[WORLDLINE UPGRADE 2.5.23] Asset-path migration complete.');
} catch (\Throwable $e) {
    \error_log('[WORLDLINE UPGRADE 2.5.23] Migration failed: ' . $e->getMessage());
}
