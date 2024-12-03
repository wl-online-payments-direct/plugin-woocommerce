<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Inpsyde\Modularity\Module\Module;
use Syde\Vendor\Inpsyde\Modularity\Package;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Core\WorldlineProperties;
return static function (string $mainPluginFile, callable $onError, Module ...$modules): Package {
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
    \load_plugin_textdomain('worldline-for-woocommerce');
    foreach ($modules as $module) {
        $package->addModule($module);
    }
    $package->boot();
    return $package;
};
