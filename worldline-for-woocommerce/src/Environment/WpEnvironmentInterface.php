<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment;

/**
 * Represents WordPress environment.
 */
interface WpEnvironmentInterface
{
    /**
     * Return current version of PHP.
     *
     * @return string
     */
    public function phpVersion() : string;
    /**
     * Return current version of WordPress.
     *
     * @return string
     */
    public function wpVersion() : string;
    /**
     * Return current version of WooCommerce, empty string if not installed.
     *
     * @return string
     */
    public function wcVersion() : string;
    /**
     * Return true if WooCommerce plugin is active, false otherwise.
     *
     * @return bool
     */
    public function isWcActive() : bool;
}
