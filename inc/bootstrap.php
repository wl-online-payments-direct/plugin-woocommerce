<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\Module;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Package;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core\WorldlineProperties;
return static function (string $mainPluginFile, callable $onError, Module ...$modules) : Package {
    $autoload = \dirname($mainPluginFile) . '/vendor/autoload.php';
    /** Older Woocommerce versions don't set version plugin headers.
     * Here we set a version header "WC requires at least", so we are able
     * to read the version from plugin properties when needed.
     */
    \add_filter('extra_plugin_headers', static function (array $headers) {
        $wcVersionHeader = 'WC requires at least';
        if (!\in_array($wcVersionHeader, $headers, \true)) {
            $headers[] = $wcVersionHeader;
        }
        return $headers;
    }, 999);
    if (\is_readable($autoload)) {
        /**
         * @psalm-suppress UnresolvableInclude
         */
        include_once $autoload;
    }
    $properties = WorldlineProperties::new($mainPluginFile);
    $package = Package::new($properties);
    \add_action($package->hookName(Package::ACTION_FAILED_BOOT), $onError);
    /**
     * @psalm-suppress MissingClosureParamType
     *
     * WP 6.7.0 changed the way textdomains are loaded.
     * Calling load_plugin_textdomain too early now produces a notice.
     * However, the just-in-time-loading early only checks wp-content/languages by default.
     * So we currently do not have a safe way to expose our plugin path,
     * resulting in potentially missing translations.
     * We're keeping both paths, hoping there are going to be amendments for this in future releases
     *
     * First, we force our plugin languages path into the textdomain loading system.
     */
    \add_filter('lang_dir_for_domain', static function ($dir, $domain) {
        if ($domain !== 'worldline-for-woocommerce') {
            return $dir;
        }
        return \WP_PLUGIN_DIR . '/worldline-for-woocommerce/languages/';
    }, 10, 3);
    /**
     * Now we expose our custom path safely.
     */
    \add_action('init', static fn() => \load_plugin_textdomain('worldline-for-woocommerce'));
    foreach ($modules as $module) {
        $package->addModule($module);
    }
    $package->boot();
    return $package;
};
