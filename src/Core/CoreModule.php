<?php

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong
declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core;

use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core\PluginActionLink\PluginActionLinkRegistry;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Container\NotFoundExceptionInterface;
class CoreModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    /**
     * @inheritDoc
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action('pre_current_active_plugins', static function () use($container) {
            /** @var PluginActionLinkRegistry $pluginActionLinksRegistry */
            $pluginActionLinksRegistry = $container->get('core.plugin.plugin_action_links.registry');
            $pluginActionLinksRegistry->init();
        });
        return \true;
    }
    /**
     * @inheritDoc
     */
    public function services() : array
    {
        static $services;
        $moduleRootPath = \dirname(__DIR__, 2);
        if ($services === null) {
            $services = (require_once "{$moduleRootPath}/inc/services.php");
        }
        /** @var callable(string): array<string, callable(ContainerInterface $c):mixed> $services */
        return $services($moduleRootPath);
    }
    /**
     * @inheritDoc
     */
    public function extensions() : array
    {
        static $extensions;
        $moduleRootPath = \dirname(__DIR__, 2);
        if ($extensions === null) {
            $extensions = (require_once "{$moduleRootPath}/inc/extensions.php");
        }
        /** @var callable(string): array<string, callable(mixed $service, ContainerInterface $c):mixed> $extensions */
        return $extensions($moduleRootPath);
    }
}
