<?php

/**
 * Plugin Name: Worldline Payments for WooCommerce
 * Description: Worldline payment Gateway for WooCommerce.
 * Version: 1.0.1
 * SHA: 014ea2e3dcc562d065dfca240b9155d6724e2d57
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.6
 * WC tested up to: 8.9.1
 * Author:      Worldline
 * Author URI:  https://syde.com
 * License:     GPL-2.0
 * Text Domain: worldline-for-woocommerce
 * Domain Path: /languages
 */
declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce;

use Syde\Vendor\Inpsyde\Modularity\Package;
if (is_readable(dirname(__FILE__) . '/vendor/autoload.php')) {
    include_once dirname(__FILE__) . '/vendor/autoload.php';
}
/**
 * Provide the plugin instance.
 *
 * @return Package
 *
 * @link https://github.com/inpsyde/modularity/blob/master/docs/Package.md#access-from-external
 */
function plugin(): Package
{
    static $package;
    if (!$package) {
        /** @var callable $bootstrap */
        $bootstrap = require __DIR__ . '/inc/bootstrap.php';
        $onError = require __DIR__ . '/inc/error.php';
        $modules = (require __DIR__ . '/inc/modules.php')();
        $modules = apply_filters('wlop.modules_list', $modules);
        $package = $bootstrap(__FILE__, $onError, ...$modules);
        do_action('wlop.plugin_init');
    }
    /** @var Package $package */
    return $package;
}
add_action('plugins_loaded', static function (): void {
    plugin();
}, 5);
