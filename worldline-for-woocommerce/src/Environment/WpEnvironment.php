<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Environment;

/**
 * Represents WordPress environment.
 */
class WpEnvironment implements WpEnvironmentInterface
{
    /**
     * @var string
     */
    protected $phpVersion;
    /**
     * @var string
     */
    protected $wpVersion;
    /**
     * @var string
     */
    protected $wcVersion;
    /**
     * @var bool
     */
    protected $isWcActive;
    /**
     * @param string $phpVersion
     * @param string $wpVersion
     * @param string $wcVersion
     * @param bool $isWcActive
     */
    public function __construct(string $phpVersion, string $wpVersion, string $wcVersion, bool $isWcActive)
    {
        $this->phpVersion = $phpVersion;
        $this->wpVersion = $wpVersion;
        $this->wcVersion = $wcVersion;
        $this->isWcActive = $isWcActive;
    }
    /**
     * @inheritDoc
     */
    public function phpVersion(): string
    {
        return $this->phpVersion;
    }
    /**
     * @inheritDoc
     */
    public function wpVersion(): string
    {
        return $this->wpVersion;
    }
    /**
     * @inheritDoc
     */
    public function wcVersion(): string
    {
        return $this->wcVersion;
    }
    /**
     * @inheritDoc
     */
    public function isWcActive(): bool
    {
        return $this->isWcActive;
    }
}
