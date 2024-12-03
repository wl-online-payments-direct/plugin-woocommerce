<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\ReturnPage;

use Exception;
use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ExtendingModule;
use Syde\Vendor\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Psr\Container\ContainerInterface;
use Syde\Vendor\Psr\Container\NotFoundExceptionInterface;
class ReturnPageModule implements ExecutableModule, ServiceModule, ExtendingModule
{
    use ModuleClassNameIdTrait;
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function run(ContainerInterface $container): bool
    {
        add_action(AssetManager::ACTION_SETUP, static function (AssetManager $assetManager) use ($container) {
            $moduleDirName = 'worldline-return-page';
            $assetsBaseUrl = $container->get('assets.module_url')($moduleDirName);
            $isOrderReceivedPage = $container->get('return_page.is_order_received_page');
            if ($isOrderReceivedPage) {
                $assetManager->register(new Script((string) $container->get('return_page.assets.handle'), "{$assetsBaseUrl}/frontend-main.js", Asset::FRONTEND), new Style("worldline-{$moduleDirName}", "{$assetsBaseUrl}/frontend-main.css", Asset::FRONTEND));
            }
        });
        add_action('init', static function () use ($container) {
            $pages = $container->get('return_page.pages');
            /** @var ReturnPage $page */
            foreach ($pages as $page) {
                $page->init();
            }
        });
        add_filter('body_class', static function (array $classes) use ($container): array {
            if (!is_order_received_page()) {
                return $classes;
            }
            $orderId = get_query_var('order-received');
            $wcOrder = wc_get_order($orderId);
            if (!$wcOrder instanceof \WC_Order) {
                return $classes;
            }
            $pages = $container->get('return_page.pages');
            assert(is_array($pages));
            $page = $pages[$wcOrder->get_payment_method()] ?? null;
            if (!$page instanceof ReturnPage || $page->checkPaymentStatus($wcOrder) !== ReturnPageStatus::PENDING) {
                return $classes;
            }
            $classes[] = 'syde-return-page-active';
            return $classes;
        }, 10, 2);
        return \true;
    }
    public function services(): array
    {
        static $services;
        if ($services === null) {
            $services = require_once dirname(__DIR__) . '/inc/services.php';
        }
        return $services();
    }
    /**
     * @inheritDoc
     */
    public function extensions(): array
    {
        static $extensions;
        if ($extensions === null) {
            $extensions = require_once dirname(__DIR__) . '/inc/extensions.php';
        }
        return $extensions();
    }
}
