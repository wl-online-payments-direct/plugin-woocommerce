<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage;

interface ReturnPageRenderInterface
{
    public function render(array $returnPageParameters): string;
}
