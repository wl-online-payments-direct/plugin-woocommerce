<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uninstall;

use Inpsyde\Assets\Asset;
use Inpsyde\Assets\AssetManager;
use Inpsyde\Assets\Script;
use Inpsyde\Assets\Style;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Container\NotFoundExceptionInterface;
class UninstallModule implements ExecutableModule, ServiceModule
{
    use ModuleClassNameIdTrait;
    public const CLEAN_DB_ACTION = 'worldlineCleanDb';
    public const CLEAN_DB_NONCE = 'worldlineCleanDbNonce';
    /**
     * @param ContainerInterface $container
     * @return bool
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function run(ContainerInterface $container) : bool
    {
        \add_action(AssetManager::ACTION_SETUP, static function (AssetManager $assetManager) use($container) {
            $moduleName = 'uninstall';
            /** @var callable(string,string):string $getModuleAssetUrl */
            $getModuleAssetUrl = $container->get('assets.get_module_asset_url');
            $assetManager->register((new Script("worldline-{$moduleName}", $getModuleAssetUrl($moduleName, 'backend-main.js'), Asset::BACKEND))->withTranslation('worldline-for-woocommerce', \WP_PLUGIN_DIR . '/worldline-for-woocommerce/languages/'));
        });
        \add_action('admin_init', static function () use($container) {
            if (self::isValidCleanDbRequest()) {
                $dbCleaner = $container->get('uninstall.db-cleaner');
                \assert($dbCleaner instanceof DatabaseCleaner);
                $dbCleaner->deleteOptions();
                \wp_safe_redirect(\remove_query_arg([self::CLEAN_DB_ACTION, self::CLEAN_DB_NONCE]));
                exit;
            }
        });
        return \true;
    }
    private static function isValidCleanDbRequest() : bool
    {
        if (!isset($_GET[self::CLEAN_DB_ACTION])) {
            return \false;
        }
        if (!\current_user_can('manage_options')) {
            return \false;
        }
        $filteredNonce = \filter_input(\INPUT_GET, self::CLEAN_DB_NONCE, \FILTER_SANITIZE_SPECIAL_CHARS);
        $isValidNonce = \is_string($filteredNonce) && \wp_verify_nonce($filteredNonce, self::CLEAN_DB_NONCE) !== \false;
        return $isValidNonce;
    }
    public function services() : array
    {
        static $services;
        if ($services === null) {
            $services = (require_once \dirname(__DIR__) . '/inc/services.php');
        }
        return $services();
    }
}
