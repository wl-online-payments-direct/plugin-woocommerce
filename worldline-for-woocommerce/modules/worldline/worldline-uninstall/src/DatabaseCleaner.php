<?php

/**
 * Clears the plugin related data from DB.
 *
 * @package Worldline\WorldlineForWoocommerce\Uninstall
 */
declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Uninstall;

use RuntimeException;
class DatabaseCleaner
{
    /**
     * @var string[]
     */
    private array $optionNames;
    /**
     * @var string[]
     */
    private array $scheduledActionNames;
    /**
     * @var string[]
     */
    private array $cleanupActionNames;
    /**
     * @param string[] $optionNames
     * @param string[] $scheduledActionNames
     * @param string[] $cleanupActionNames
     */
    public function __construct(array $optionNames, array $scheduledActionNames, array $cleanupActionNames)
    {
        $this->optionNames = $optionNames;
        $this->scheduledActionNames = $scheduledActionNames;
        $this->cleanupActionNames = $cleanupActionNames;
    }
    /**
     * Deletes the given options from the database.
     *
     * @throws RuntimeException If a problem occurs during deletion.
     */
    public function deleteOptions() : void
    {
        foreach ($this->optionNames as $optionName) {
            \delete_option($optionName);
        }
    }
    /**
     * Clears the given scheduled actions.
     *
     * @throws RuntimeException If a problem occurs during clearing.
     */
    public function clearScheduledActions() : void
    {
        /*
         * Action Scheduler comes from WooCommerce, and this runs from the admin
         * "reset data" request as well as from uninstall. That request has no
         * try/catch around it, so on a site where WooCommerce is inactive - which
         * WordPress only prevents from 6.5 on, and this plugin supports 6.3 - an
         * undefined function here would take down the admin page instead of
         * cleaning up. There is nothing scheduled in that case anyway.
         */
        if (!\function_exists('as_unschedule_action')) {
            return;
        }
        foreach ($this->scheduledActionNames as $actionName) {
            \as_unschedule_action($actionName);
        }
    }
    /**
     * Runs the given cleanup actions.
     *
     * @throws RuntimeException If a problem occurs during clearing.
     */
    public function runCleanupActions() : void
    {
        foreach ($this->cleanupActionNames as $actionName) {
            \do_action($actionName);
        }
    }
    /**
     * Performs a complete cleanup by deleting stored options,
     * unscheduling actions, and clearing any custom actions.
     *
     * @throws RuntimeException If a problem occurs during one of the cleanup steps.
     */
    public function clearAll() : void
    {
        $this->deleteOptions();
        $this->clearScheduledActions();
        $this->runCleanupActions();
    }
}
