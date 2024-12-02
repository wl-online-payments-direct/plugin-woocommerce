<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Documentation\Renderer;

class LinksRenderer
{
    protected string $contactUsUrl;
    protected string $documentationUrl;
    protected string $createAccountUrl;
    protected string $viewAccountUrl;
    public function __construct(string $contactUsUrl, string $documentationUrl, string $createAccountUrl, string $viewAccountUrl)
    {
        $this->contactUsUrl = $contactUsUrl;
        $this->documentationUrl = $documentationUrl;
        $this->createAccountUrl = $createAccountUrl;
        $this->viewAccountUrl = $viewAccountUrl;
    }
    public function render(): string
    {
        $links = [sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            __('%1$sContact Us%2$s', 'worldline-for-woocommerce'),
            '<a target="_blank" href="' . esc_url($this->contactUsUrl) . '">',
            '</a>'
        ), sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            __('%1$sDocumentation%2$s', 'worldline-for-woocommerce'),
            '<a target="_blank" href="' . esc_url($this->documentationUrl) . '">',
            '</a>'
        ), sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            __('%1$sCreate Account%2$s', 'worldline-for-woocommerce'),
            '<a target="_blank" href="' . esc_url($this->createAccountUrl) . '">',
            '</a>'
        ), sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            __('%1$sView Account%2$s', 'worldline-for-woocommerce'),
            '<a target="_blank" href="' . esc_url($this->viewAccountUrl) . '">',
            '</a>'
        )];
        return '<nav>' . implode(' | ', $links) . '</nav>';
    }
}
