<?php

namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce;

use wpdb;
class Plugin
{
    protected static ?Plugin $instance = null;
    private wpdb $db;
    private string $plugin_file;
    public static function instance(wpdb $wpdb, string $plugin_file) : Plugin
    {
        if (self::$instance === null) {
            self::$instance = new self($wpdb, $plugin_file);
        }
        self::$instance->runUpgradeCheck();
        return self::$instance;
    }
    public function __construct(wpdb $wpdb, string $plugin_file)
    {
        $this->db = $wpdb;
        $this->plugin_file = $plugin_file;
    }
    /**
     * Checks if plugin version changed and runs upgrade scripts.
     */
    private function runUpgradeCheck() : void
    {
        $stored_version = \get_option('worldline_plugin_version', '0.0.0');
        $current_version = $this->get_plugin_version();
        if (\version_compare($stored_version, $current_version, '<')) {
            $this->upgrade($stored_version, $current_version);
            \update_option('worldline_plugin_version', $current_version);
        }
    }
    /**
     * Runs upgrade scripts for all intermediate versions.
     */
    private function upgrade(string $previous_version, string $current_version) : void
    {
        $upgrade_dir = \dirname($this->plugin_file) . '/src/upgrade/';
        if (!\is_dir($upgrade_dir)) {
            return;
        }
        $files = \glob($upgrade_dir . 'upgrade-*.php');
        \sort($files, \SORT_NATURAL);
        foreach ($files as $file) {
            $file_version = \str_replace(['upgrade-', '.php'], '', \basename($file));
            if (\version_compare($previous_version, $file_version, '<') && \version_compare($file_version, $current_version, '<=')) {
                include_once $file;
                \error_log("[WORLDLINE] ✅ Ran upgrade for version {$file_version}");
            }
        }
    }
    private function get_plugin_version() : string
    {
        $data = \get_file_data($this->plugin_file, ['Version' => 'Version']);
        return $data['Version'] ?? '0.0.0';
    }
}
