<?php

/**
 * Uninstalls the plugin.
 *
 * @package Inpsyde\WorldlineForWoocommerce
 */
declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Package;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uninstall\DatabaseCleaner;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
if (!\defined('WP_UNINSTALL_PLUGIN')) {
    die('Direct access not allowed.');
}
$mainPluginFile = __DIR__ . "/worldline-for-woocommerce.php";
if (!\file_exists($mainPluginFile)) {
    return;
}
require $mainPluginFile;
(static function () : void {
    $autoloadPath = __DIR__ . "/vendor/autoload.php";
    if (\file_exists($autoloadPath) && !\class_exists('Syde\\Vendor\\Worldline\\Inpsyde\\WorldlineForWoocommerce\\CoreModule')) {
        require $autoloadPath;
    }
    try {
        $bootstrap = (require __DIR__ . '/inc/bootstrap.php');
        $onError = (require __DIR__ . '/inc/error.php');
        $modules = (require __DIR__ . '/inc/modules.php')();
        $modules = \apply_filters('wlop.modules_list', $modules);
        $package = $bootstrap(__FILE__, $onError, ...$modules);
        \assert($package instanceof Package);
        $container = $package->container();
        \assert($container instanceof ContainerInterface);
        $shouldClearDb = $container->get('config.clear_data_on_uninstall');
        if ($shouldClearDb !== \true) {
            return;
        }
        global $wpdb;
        $table = $wpdb->prefix . 'product_type';
        $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
        $dbCleaner = $container->get('uninstall.db-cleaner');
        \assert($dbCleaner instanceof DatabaseCleaner);
        $dbCleaner->clearAll();
    } catch (\Throwable $throwable) {
        $message = \sprintf('<strong>Error:</strong> %s <br><pre>%s</pre>', $throwable->getMessage(), $throwable->getTraceAsString());
        \add_action('all_admin_notices', static function () use($message) {
            $class = 'notice notice-error';
            \printf('<div class="%1$s"><p>%2$s</p></div>', \esc_attr($class), \wp_kses_post($message));
        });
    }
})();
