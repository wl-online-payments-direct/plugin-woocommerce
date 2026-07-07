<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\ReturnPage;

class ReturnPageRender implements ReturnPageRenderInterface
{
    public function render(array $parameters) : string
    {
        $message = '';
        if (!empty($parameters['message'])) {
            $message = $parameters['message'];
        }
        return "<span class='worldline-return-page-order-payment-status__message'> \n                        {$message}\n                   </span>";
    }
}
