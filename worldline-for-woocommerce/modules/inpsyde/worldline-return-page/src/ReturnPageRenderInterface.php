<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage;

interface ReturnPageRenderInterface
{
    public function render(array $returnPageParameters) : string;
}
