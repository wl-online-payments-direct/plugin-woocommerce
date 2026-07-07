<?php

// phpcs:disable Worldline.CodeQuality.LineLength.TooLong
declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Core;

use Worldline\Vendor\Worldline\Modularity\Module\ExecutableModule;
use Worldline\Vendor\Worldline\Modularity\Module\ExtendingModule;
use Worldline\Vendor\Worldline\Modularity\Module\ModuleClassNameIdTrait;
use Worldline\Vendor\Worldline\Modularity\Module\ServiceModule;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Admin\CancelAuthorizationUi;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Admin\CaptureAuthorizationUi;
use Worldline\Vendor\Worldline\WorldlineForWoocommerce\Core\PluginActionLink\PluginActionLinkRegistry;
use Worldline\Vendor\Psr\Container\ContainerExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Container\NotFoundExceptionInterface;
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
        \add_action('admin_init', static function () use($container) {
            /** @var CancelAuthorizationUi $ui */
            $ui = $container->get('core.admin.cancel_authorization_ui');
            $ui->register();
            /** @var CaptureAuthorizationUi $captureUi */
            $captureUi = $container->get('core.admin.capture_authorization_ui');
            $captureUi->register();
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
