<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\PaymentGateway;

interface GatewayIconsRendererInterface
{
    /**
     * Renders gateway icons.
     *
     * @return string Rendered HTML.
     */
    public function renderIcons(): string;
}
