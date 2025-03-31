<?php

/**
 * Plugin Name: Worldline Payments for WooCommerce
 * Description: Worldline payment gateway for WooCommerce.
 * Version: 2.1.0
 * SHA: d142311393c297e412a6c0ac9d2723a230123283
 * Requires at least: 6.3
 * Requires PHP: 7.4
 * Requires Plugins: woocommerce
 * WC requires at least: 8.6
 * WC tested up to: 9.5
 * Author:      Worldline
 * Author URI:  https://syde.com
 * Text Domain: worldline-for-woocommerce
 * Domain Path: /languages
 */
declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Package;
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
