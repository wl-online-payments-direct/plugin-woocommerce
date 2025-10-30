<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core\PluginActionLink;

class PluginActionLinkRegistry
{
    private string $pluginMainFile;
    /**
     * @var PluginActionLink[]
     */
    private array $pluginActionLinks;
    /**
     * @var PluginActionLink[]
     */
    private array $pluginMetaLinks;
    public function __construct(string $pluginMainFile, array $pluginActionLinks, array $pluginMetaLinks)
    {
        $this->pluginMainFile = $pluginMainFile;
        $this->pluginActionLinks = $pluginActionLinks;
        $this->pluginMetaLinks = $pluginMetaLinks;
    }
    public function init() : void
    {
        \add_filter('plugin_action_links', function (array $links, string $pluginFile) : array {
            return $this->addPluginLinks($links, $pluginFile, $this->pluginActionLinks);
        }, 10, 2);
        \add_filter('plugin_row_meta', function (array $links, string $pluginFile) : array {
            return $this->addPluginLinks($links, $pluginFile, $this->pluginMetaLinks);
        }, 10, 2);
    }
    protected function addPluginLinks(array $links, string $pluginFile, array $extraLinks) : array
    {
        if ($pluginFile !== $this->pluginMainFile) {
            return $links;
        }
        $extraLinksAssoc = \array_reduce($extraLinks, static function (array $extraLinks, PluginActionLink $link) : array {
            $extraLinks[$link->slug()] = $link;
            return $extraLinks;
        }, []);
        return \array_merge($extraLinksAssoc, $links);
    }
}
