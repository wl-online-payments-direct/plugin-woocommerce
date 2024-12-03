<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Core\PluginActionLink;

use Syde\Vendor\Psr\Http\Message\UriInterface;
class PluginActionLink
{
    /**
     * @var string
     */
    private $slug;
    /**
     * @var string
     */
    private $label;
    /**
     * @var UriInterface
     */
    private $uri;
    private bool $newTab;
    public function __construct(string $slug, string $label, UriInterface $uri, bool $newTab = \false)
    {
        $this->slug = $slug;
        $this->label = $label;
        $this->uri = $uri;
        $this->newTab = $newTab;
    }
    public function slug(): string
    {
        return $this->slug;
    }
    public function __toString(): string
    {
        $target = $this->newTab ? ' _blank' : '_self';
        return sprintf('<a target="%s" href="%s">%s</a>', $target, esc_url((string) $this->uri), esc_html($this->label));
    }
}
