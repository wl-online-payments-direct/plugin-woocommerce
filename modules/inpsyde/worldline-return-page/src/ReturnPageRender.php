<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\ReturnPage;

class ReturnPageRender implements ReturnPageRenderInterface
{
    public function render(array $parameters) : string
    {
        $message = '';
        if (!empty($parameters['message'])) {
            $message = $parameters['message'];
        }
        return "<span class='syde-return-page-order-payment-status__message'> \n                        {$message}\n                   </span>";
    }
}
