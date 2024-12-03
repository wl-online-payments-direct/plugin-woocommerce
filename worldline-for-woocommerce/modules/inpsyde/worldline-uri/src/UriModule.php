<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Uri;

use Syde\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Inpsyde\Modularity\Module\ServiceModule;
/**
 * The Uri module.
 */
class UriModule implements ServiceModule
{
    use ModuleClassNameIdTrait;
    /**
     * @inheritDoc
     */
    public function services(): array
    {
        static $services;
        if ($services === null) {
            $services = require_once dirname(__DIR__) . '/inc/services.php';
        }
        return $services();
    }
}
