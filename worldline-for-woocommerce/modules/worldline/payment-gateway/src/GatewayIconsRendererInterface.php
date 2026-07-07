<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\PaymentGateway;

interface GatewayIconsRendererInterface
{
    /**
     * Renders gateway icons.
     *
     * @return string Rendered HTML.
     */
    public function renderIcons() : string;
}
