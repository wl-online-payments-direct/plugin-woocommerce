<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Utils;

class FileBasedLockerFactory implements LockerFactoryInterface
{
    private int $timeout;
    private string $tempDir;
    public function __construct(int $timeout, string $tempDir)
    {
        $this->timeout = $timeout;
        $this->tempDir = $tempDir;
    }
    public function create(int $orderId) : LockerInterface
    {
        $lockFilePath = "{$this->tempDir}/wlop_order_{$orderId}.lock";
        return new FileBasedLocker($this->timeout, $lockFilePath);
    }
}
