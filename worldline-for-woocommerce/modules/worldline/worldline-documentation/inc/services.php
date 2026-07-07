<?php

declare (strict_types=1);
namespace Worldline\Vendor;

use Worldline\Vendor\Dhii\Services\Factories\Constructor;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Documentation\Renderer\LinksRenderer;
return static function () : array {
    return ['documentation.links_renderer' => new Constructor(LinksRenderer::class, ['core.contact_us_url', 'core.documentation_url', 'core.test_create_account_url', 'core.live_create_account_url', 'core.test_view_account_url', 'core.live_view_account_url', 'config.is_live'])];
};
