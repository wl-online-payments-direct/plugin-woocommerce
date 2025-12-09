<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Dhii\Package\Version\StringVersionFactoryInterface;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Alias;
use Syde\Vendor\Worldline\Dhii\Services\Factories\Value;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
use Syde\Vendor\Worldline\Dhii\Validation\ValidatorInterface;
use Syde\Vendor\Worldline\Dhii\Validator\CallbackValidator;
use Syde\Vendor\Worldline\Dhii\Validator\CompositeValidator;
use Syde\Vendor\Worldline\Dhii\Versions\StringVersionFactory;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Package;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Properties\PluginProperties;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Properties\Properties;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core\PluginActionLink\PluginActionLink;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Core\PluginActionLink\PluginActionLinkRegistry;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment\WpEnvironmentFactory;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment\WpEnvironmentFactoryInterface;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment\WpEnvironmentInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Http\Message\UriFactoryInterface;
use Syde\Vendor\Worldline\Psr\Http\Message\UriInterface;
return static function (string $rootPath) : array {
    $config = (require __DIR__ . '/../config.php');
    return ['assets.get_module_asset_url' => new Factory([Package::PROPERTIES], static function (PluginProperties $props) : callable {
        return static function (string $moduleName, string $assetName) use($props) : string {
            return $props->baseUrl() . "assets/{$moduleName}-{$assetName}";
        };
    }), 'assets.get_module_static_asset_url' => new Factory([Package::PROPERTIES], static function (PluginProperties $props) : callable {
        return static function (string $packageName, string $assetName) use($props) : string {
            return $props->baseUrl() . "modules/inpsyde/{$packageName}/assets/{$assetName}";
        };
    }), 'core.environment_validator' => static function (ContainerInterface $container) : ValidatorInterface {
        /** @var ValidatorInterface $phpVersionValidator */
        $phpVersionValidator = $container->get('core.php_version_validator');
        /** @var ValidatorInterface $wpVersionValidator */
        $wpVersionValidator = $container->get('core.wp_version_validator');
        /** @var ValidatorInterface $wcVersionValidator */
        $wcVersionValidator = $container->get('core.wc_version_validator');
        /** @var ValidatorInterface $wcActiveValidator */
        $wcActiveValidator = $container->get('core.wc_active_validator');
        return new CompositeValidator([$phpVersionValidator, $wpVersionValidator, $wcVersionValidator, $wcActiveValidator]);
    }, 'core.php_version_validator' => static function (ContainerInterface $container) : ValidatorInterface {
        /** @var Properties $pluginProperties */
        $pluginProperties = $container->get('properties');
        return new CallbackValidator(static function (WpEnvironmentInterface $environment) use($pluginProperties) : ?string {
            if (\version_compare($environment->phpVersion(), (string) $pluginProperties->requiresPhp(), '>=')) {
                return null;
            }
            return \sprintf('Required PHP version is %1$s, but the current one is %2$s', (string) $pluginProperties->requiresPhp(), $environment->phpVersion());
        });
    }, 'core.wp_version_validator' => static function (ContainerInterface $container) : ValidatorInterface {
        /** @var Properties $pluginProperties */
        $pluginProperties = $container->get('properties');
        return new CallbackValidator(static function (WpEnvironmentInterface $environment) use($pluginProperties) : ?string {
            if (\version_compare($environment->wpVersion(), (string) $pluginProperties->requiresWp(), '>=')) {
                return null;
            }
            return \sprintf('Required WordPress version is %1$s, but the current one is %2$s', (string) $pluginProperties->requiresWp(), $environment->wpVersion());
        });
    }, 'core.wc_version_validator' => static function (ContainerInterface $container) : ValidatorInterface {
        /** @var Properties $pluginProperties */
        $pluginProperties = $container->get('properties');
        $requiredWcVersion = (string) $pluginProperties->get('WC requires at least');
        return new CallbackValidator(static function (WpEnvironmentInterface $environment) use($requiredWcVersion) : ?string {
            if (empty($environment->wcVersion()) || \version_compare($environment->wcVersion(), $requiredWcVersion, '>=')) {
                return null;
            }
            return \sprintf('Required WooCommerce version is %1$s, but the current one is %2$s', $requiredWcVersion, $environment->wcVersion());
        });
    }, 'core.wc_active_validator' => static function (ContainerInterface $container) : ValidatorInterface {
        /** @var Properties $pluginProperties */
        $pluginProperties = $container->get('properties');
        $pluginName = $pluginProperties->name();
        return new CallbackValidator(static function (WpEnvironmentInterface $environment) use($pluginName) : ?string {
            if ($environment->isWcActive()) {
                return null;
            }
            return \sprintf('%1$s requires WooCommerce to be active.', $pluginName);
        });
    }, 'core.wp_environment' => static function (ContainerInterface $container) : WpEnvironmentInterface {
        /** @var WpEnvironmentFactoryInterface $environmentFactory */
        $environmentFactory = $container->get('core.wp_environment_factory');
        return $environmentFactory->createFromGlobals();
    }, 'core.wp_environment_factory' => static function (ContainerInterface $container) : WpEnvironmentFactoryInterface {
        /** @var StringVersionFactoryInterface $versionFactory */
        $versionFactory = $container->get('core.string_version_factory');
        /** @var string $eventNameEnvironmentValidationFailed */
        $eventNameEnvironmentValidationFailed = $container->get('core.event_name_environment_validation_failed');
        return new WpEnvironmentFactory($versionFactory, $eventNameEnvironmentValidationFailed);
    }, 'core.string_version_factory' => static function () : StringVersionFactoryInterface {
        return new StringVersionFactory();
    }, 'core.event_name_environment_validation_failed' => static function () : string {
        return 'wlop.environment_validation_failed';
    }, 'core.plugin.plugin_action_links.registry' => new Factory(['core.main_plugin_file', 'core.plugin.plugin_action_links', 'core.plugin.plugin_meta_links'], static function (string $mainFilePath, array $pluginActionLinks, array $pluginMetaLinks) : PluginActionLinkRegistry {
        /** @var PluginActionLink[] $pluginActionLinks */
        return new PluginActionLinkRegistry(\plugin_basename($mainFilePath), $pluginActionLinks, $pluginMetaLinks);
    }), 'core.main_plugin_file' => static function (ContainerInterface $container) : string {
        /** @var PluginProperties $properties */
        $properties = $container->get(Package::PROPERTIES);
        return \sprintf('%1$s/%2$s.php', $properties->basePath(), $properties->baseName());
    }, 'core.plugin.plugin_action_links' => new Factory(['core.http.settings_url'], static function (UriInterface $settingsUrl) : array {
        return [new PluginActionLink('settings', \__('Settings', 'worldline-for-woocommerce'), $settingsUrl)];
    }), 'core.plugin.plugin_meta_links' => new Factory(['core.contact_us_url_builder'], static function (UriInterface $contactUsUrlBuilder) : array {
        return [new PluginActionLink('contact_us', \__('Contact us', 'worldline-for-woocommerce'), $contactUsUrlBuilder, \true)];
    }), 'core.http.settings_url' => new Factory(['core.uri.factory'], static function (UriFactoryInterface $factory) : UriInterface {
        return $factory->createUri(\sprintf('%s?%s', \admin_url('admin.php'), \http_build_query(['page' => 'wc-settings', 'tab' => 'checkout', 'section' => 'worldline-for-woocommerce'])));
    }), 'core.contact_us_url' => new Value($config['CONTACT_URL']), 'core.documentation_url' => new Value($config['DOCUMENTATION_URL']), 'core.test_create_account_url' => new Value($config['TEST_CREATE_ACCOUNT_URL']), 'core.live_create_account_url' => new Value($config['LIVE_CREATE_ACCOUNT_URL']), 'core.test_view_account_url' => new Value($config['TEST_VIEW_ACCOUNT_URL']), 'core.live_view_account_url' => new Value($config['LIVE_VIEW_ACCOUNT_URL']), 'core.contact_us_url_builder' => new Factory(['core.uri.factory', 'core.contact_us_url'], static function (UriFactoryInterface $uriFactory, string $contactUsUrl) : UriInterface {
        return $uriFactory->createUri($contactUsUrl);
    }), 'core.uri.factory' => new Alias('uri.factory'), 'core.webhooks.namespace' => new Value('inpsyde/worldline-for-woocommerce'), 'core.webhooks.route' => new Value('/listener/notifications'), 'core.webhooks.notification_url' => new Factory(['webhooks.namespace', 'webhooks.rest_route', 'core.uri.factory'], static function (string $restNamespace, string $restRoute, UriFactoryInterface $uriFactory) : ?UriInterface {
        $blogId = \get_current_blog_id();
        $path = $restNamespace . $restRoute;
        try {
            $restUrl = \get_rest_url($blogId, $path);
        } catch (\Throwable $exception) {
            return null;
        }
        return $uriFactory->createUri($restUrl);
    }), 'inpsyde_logger.native_wc_logger' => static function () : \WC_Logger_Interface {
        return \wc_get_logger();
    }, 'core.is_debug_logging_enabled' => new Alias('config.debug_logging'), 'core.is_logging_enabled' => new Factory(['core.is_debug_logging_enabled'], static function (bool $debugLogging) : bool {
        return $debugLogging || \apply_filters('wlop.logging_enabled', \true);
    })];
};
