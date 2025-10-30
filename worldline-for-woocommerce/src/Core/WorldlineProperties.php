<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Properties\PluginProperties;
class WorldlineProperties extends PluginProperties
{
    /**
     * @param string $pluginMainFile
     *
     * @return PluginProperties
     */
    public static function new(string $pluginMainFile) : PluginProperties
    {
        return new self($pluginMainFile);
    }
    public function isDebug() : bool
    {
        /**
         * We do not wish to follow Modularity's default behaviour of piggybacking on WP_DEBUG.
         * Experience tells us that there are too many production systems running
         * with the WP_DEBUG flag enabled for whatever reason. We have no power over
         * these systems to "fix" our plugin's behaviour, but the admins of these systems
         * certainly do have the power to complain on our support forums.
         * Since we desire to use debug mode for a couple of development-centric features such as
         * loudly throwing exceptions, we need to be careful about enabling it.
         *
         * As a consequence, we implement our own debug flag here which pretty much guarantees
         * it will never be used accidentally.
         */
        return (bool) \getenv('WORLDLINE_DEBUG');
    }
}
