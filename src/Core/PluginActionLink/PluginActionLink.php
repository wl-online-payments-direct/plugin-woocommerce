<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core\PluginActionLink;

use Syde\Vendor\Worldline\Psr\Http\Message\UriInterface;
class PluginActionLink
{
    private string $slug;
    private string $label;
    private UriInterface $uri;
    private bool $newTab;
    public function __construct(string $slug, string $label, UriInterface $uri, bool $newTab = \false)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->uri = $uri;
        $this->newTab = $newTab;
    }
    public function slug() : string
    {
        return $this->slug;
    }
    public function __toString() : string
    {
        $target = $this->newTab ? ' _blank' : '_self';
        return \sprintf('<a target="%s" href="%s">%s</a>', $target, \esc_url((string) $this->uri), \esc_html($this->label));
    }
}
