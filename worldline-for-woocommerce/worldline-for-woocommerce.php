<?php

/**
 * Plugin Name: Worldline Global Online Pay for WooCommerce
 * Description: Worldline Global Online Pay for WooCommerce.
 * Version:     2.4.5
 * SHA:        c6f299e
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.6
 * WC tested up to: 9.5
 * Author:      Worldline
 * Author URI:  https://worldline.com
 * Text Domain: worldline-for-woocommerce
 * Domain Path: /languages
 */

declare(strict_types=1);

namespace Inpsyde\WorldlineForWoocommerce;

use Inpsyde\Modularity\Package;

if (is_readable(dirname(__FILE__) . '/vendor/autoload.php')) {
    include_once dirname(__FILE__) . '/vendor/autoload.php';
}


add_action('plugins_loaded', static function (): void {
    static $package;

    if (!$package) {
        /** @var callable $bootstrap */
        $bootstrap = require __DIR__ . '/inc/bootstrap.php';
        $onError = require __DIR__ . '/inc/error.php';
        $modules = (require __DIR__ . '/inc/modules.php')();
        $modules = apply_filters('wlop.modules_list', $modules);

        $package = $bootstrap(
            __FILE__,
            $onError,
            ...$modules
        );
        do_action('wlop.plugin_init');
    }
}, 5);
