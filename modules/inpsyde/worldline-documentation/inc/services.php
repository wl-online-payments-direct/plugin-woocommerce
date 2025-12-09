<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Documentation\Renderer\LinksRenderer;
return static function () : array {
    return ['documentation.links_renderer' => new Constructor(LinksRenderer::class, ['core.contact_us_url', 'core.documentation_url', 'core.test_create_account_url', 'core.live_create_account_url', 'core.test_view_account_url', 'core.live_view_account_url', 'config.is_live'])];
};
