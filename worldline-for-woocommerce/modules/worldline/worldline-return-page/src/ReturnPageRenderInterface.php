<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage;

interface ReturnPageRenderInterface
{
    public function render(array $returnPageParameters) : string;
}
