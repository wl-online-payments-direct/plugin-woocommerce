<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Environment;

use Syde\Vendor\Worldline\Dhii\Package\Version\StringVersionFactoryInterface;
use Exception;
use WooCommerce;
use function do_action;
use const WC_VERSION;
/**
 * Service creating objects representing WordPress environment.
 */
class WpEnvironmentFactory implements WpEnvironmentFactoryInterface
{
    protected StringVersionFactoryInterface $versionFactory;
    protected string $eventNameEnvironmentValidationFailed;
    /**
     * @param StringVersionFactoryInterface $versionFactory
     * @param string $eventNameEnvironmentValidationFailed
     */
    public function __construct(StringVersionFactoryInterface $versionFactory, string $eventNameEnvironmentValidationFailed)
    {
        $this->versionFactory = $versionFactory;
        $this->eventNameEnvironmentValidationFailed = $eventNameEnvironmentValidationFailed;
    }
    /**
     * @inheritDoc
     */
    public function createFromGlobals() : WpEnvironmentInterface
    {
        return new WpEnvironment($this->phpVersion(), $this->wpVersion(), $this->wcVersion(), $this->isWcActive());
    }
    /**
     * Get current PHP version.
     *
     * @return string
     */
    protected function phpVersion() : string
    {
        try {
            return (string) $this->versionFactory->createVersionFromString((string) \phpversion());
        } catch (Exception $exception) {
            do_action($this->eventNameEnvironmentValidationFailed, ['reason' => 'couldn\'t get PHP version', 'details' => $exception->getMessage()]);
            return '';
        }
    }
    /**
     * Get current WP version.
     *
     * @return string
     */
    protected function wpVersion() : string
    {
        global $wp_version;
        try {
            return (string) $this->versionFactory->createVersionFromString((string) $wp_version);
        } catch (Exception $exception) {
            do_action($this->eventNameEnvironmentValidationFailed, ['reason' => 'couldn\'t get WordPress version', 'details' => $exception->getMessage()]);
            return '';
        }
    }
    /**
     * Get current WC version.
     *
     * @return string
     */
    protected function wcVersion() : string
    {
        if (!\defined('WC_VERSION')) {
            return '';
        }
        try {
            return (string) $this->versionFactory->createVersionFromString(WC_VERSION);
        } catch (Exception $exception) {
            do_action($this->eventNameEnvironmentValidationFailed, ['reason' => 'couldn\'t get WooCommerce version', 'details' => $exception->getMessage()]);
            return '';
        }
    }
    /**
     * Check whether WC active.
     *
     * @return bool
     */
    protected function isWcActive() : bool
    {
        return \class_exists(WooCommerce::class);
    }
}
