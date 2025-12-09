<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Documentation\Renderer;

class LinksRenderer
{
    protected string $contactUsUrl;
    protected string $documentationUrl;
    protected string $testCreateAccountUrl;
    protected string $liveCreateAccountUrl;
    protected string $testViewAccountUrl;
    protected string $liveViewAccountUrl;
    protected bool $isLive;
    public function __construct(string $contactUsUrl, string $documentationUrl, string $testCreateAccountUrl, string $liveCreateAccountUrl, string $testViewAccountUrl, string $liveViewAccountUrl, bool $isLive)
    {
        $this->contactUsUrl = $contactUsUrl;
        $this->documentationUrl = $documentationUrl;
        $this->testCreateAccountUrl = $testCreateAccountUrl;
        $this->liveCreateAccountUrl = $liveCreateAccountUrl;
        $this->testViewAccountUrl = $testViewAccountUrl;
        $this->liveViewAccountUrl = $liveViewAccountUrl;
        $this->isLive = $isLive;
    }
    public function render() : string
    {
        $links = [\sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            \__('%1$sContact Us%2$s', 'worldline-for-woocommerce'),
            '<a target="_blank" href="' . \esc_url($this->contactUsUrl) . '">',
            '</a>'
        ), \sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            \__('%1$sDocumentation%2$s', 'worldline-for-woocommerce'),
            '<a target="_blank" href="' . \esc_url($this->documentationUrl) . '">',
            '</a>'
        ), \sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            \__('%1$sCreate Account%2$s', 'worldline-for-woocommerce'),
            '<a id="wlopTestCreateAccountLink" target="_blank" href="' . \esc_url($this->testCreateAccountUrl) . '"' . ($this->isLive ? ' style="display: none;"' : '') . '>',
            '</a>'
        ) . \sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            \__('%1$sCreate Account%2$s', 'worldline-for-woocommerce'),
            '<a id="wlopLiveCreateAccountLink" target="_blank" href="' . \esc_url($this->liveCreateAccountUrl) . '"' . ($this->isLive ? '' : ' style="display: none;"') . '>',
            '</a>'
        ), \sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            \__('%1$sView Account%2$s', 'worldline-for-woocommerce'),
            '<a id="wlopTestViewAccountLink" target="_blank" href="' . \esc_url($this->testViewAccountUrl) . '"' . ($this->isLive ? ' style="display: none;"' : '') . '>',
            '</a>'
        ) . \sprintf(
            // translators: %1$s, %2$s - <a> link tags.
            \__('%1$sView Account%2$s', 'worldline-for-woocommerce'),
            '<a id="wlopLiveViewAccountLink" target="_blank" href="' . \esc_url($this->liveViewAccountUrl) . '"' . ($this->isLive ? '' : ' style="display: none;"') . '>',
            '</a>'
        )];
        return '<nav>' . \implode(' | ', $links) . '</nav>';
    }
}
