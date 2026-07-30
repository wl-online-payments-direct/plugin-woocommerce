<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Utils;

class FileBasedLockerFactory implements LockerFactoryInterface
{
    private int $timeout;
    private string $tempDir;
    private string $prefix;
    public function __construct(int $timeout, string $tempDir, string $prefix = 'wlop_order')
    {
        $this->timeout = $timeout;
        $this->tempDir = $tempDir;
        $this->prefix = $prefix;
    }
    public function create(int $orderId) : LockerInterface
    {
        $lockFilePath = "{$this->tempDir}/{$this->prefix}_{$orderId}.lock";
        return new FileBasedLocker($this->timeout, $lockFilePath);
    }
}
