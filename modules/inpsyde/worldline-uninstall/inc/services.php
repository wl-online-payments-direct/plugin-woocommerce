<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline;

use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uninstall\DatabaseCleaner;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uninstall\UninstallModule;
use Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\WorldlinePaymentGateway\GatewayIds;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Dhii\Services\Factory;
return static function () : array {
    return ['uninstall.worldline-all-option-names' => static function (ContainerInterface $container) : array {
        $gatewaySettingOptions = \array_map(static function (string $gatewayId) : string {
            return "woocommerce_{$gatewayId}_settings";
        }, GatewayIds::ALL);
        return [...$gatewaySettingOptions];
    }, 'uninstall.worldline-all-cleanup-action-names' => static function (ContainerInterface $container) : array {
        return [];
    }, 'uninstall.worldline-all-scheduled-action-names' => static function () : array {
        return ['wlop_update_status'];
    }, 'uninstall.db-cleaner' => new Factory(['uninstall.worldline-all-option-names', 'uninstall.worldline-all-scheduled-action-names', 'uninstall.worldline-all-cleanup-action-names'], static function (array $optionNames, array $actionNames, array $scheduledActionNames) : DatabaseCleaner {
        return new DatabaseCleaner($optionNames, $actionNames, $scheduledActionNames);
    }), 'uninstall.db-cleaner-url' => static function () : string {
        $nonce = \wp_create_nonce(UninstallModule::CLEAN_DB_NONCE);
        return \add_query_arg([UninstallModule::CLEAN_DB_ACTION => '1', UninstallModule::CLEAN_DB_NONCE => $nonce]);
    }];
};
