<?php

/**
 * Uninstalls the plugin.
 *
 * @package Worldline\WorldlineForWoocommerce
 */
declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Worldline\Modularity\Package;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Uninstall\DatabaseCleaner;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Worldline\Vendor\Psr\Container\ContainerInterface;
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
    if (\file_exists($autoloadPath) && !\class_exists('Worldline\\Vendor\\Worldline\\WorldlineForWoocommerce\\CoreModule')) {
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
        $dbCleaner = $container->get('uninstall.db-cleaner');
        \assert($dbCleaner instanceof DatabaseCleaner);
        $cleanCurrentSite = static function () use($dbCleaner) : void {
            $settings = \get_option('woocommerce_' . GatewayIds::HOSTED_CHECKOUT . '_settings', []);
            $shouldClearDb = \is_array($settings) && ($settings['clear_data_on_uninstall'] ?? 'no') === 'yes';
            if (!$shouldClearDb) {
                return;
            }
            global $wpdb;
            $table = $wpdb->prefix . 'product_type';
            $wpdb->query("DROP TABLE IF EXISTS `{$table}`");
            $dbCleaner->clearAll();
        };
        if (!\is_multisite()) {
            $cleanCurrentSite();
            return;
        }
        $siteIds = \get_sites(['fields' => 'ids', 'number' => 0]);
        foreach ($siteIds as $siteId) {
            \switch_to_blog((int) $siteId);
            try {
                $cleanCurrentSite();
            } finally {
                \restore_current_blog();
            }
        }
    } catch (\Throwable $throwable) {
        $message = \sprintf('<strong>Error:</strong> %s <br><pre>%s</pre>', $throwable->getMessage(), $throwable->getTraceAsString());
        \add_action('all_admin_notices', static function () use($message) {
            $class = 'notice notice-error';
            \printf('<div class="%1$s"><p>%2$s</p></div>', \esc_attr($class), \wp_kses_post($message));
        });
    }
})();
