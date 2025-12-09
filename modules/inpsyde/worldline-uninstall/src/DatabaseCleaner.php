<?php

/**
 * Clears the plugin related data from DB.
 *
 * @package Inpsyde\WorldlineForWoocommerce\Uninstall
 */
declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uninstall;

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
