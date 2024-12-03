<?php

declare (strict_types=1);
namespace Syde\Vendor;

use Syde\Vendor\Dhii\Services\Factories\Constructor;
use Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Documentation\Renderer\LinksRenderer;
return static function (): array {
    return ['documentation.links_renderer' => new Constructor(LinksRenderer::class, ['core.contact_us_url', 'core.documentation_url', 'core.create_account_url', 'core.view_account_url'])];
};
