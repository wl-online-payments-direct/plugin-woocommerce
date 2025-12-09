<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Utils;

class FileBasedLocker implements LockerInterface
{
    private int $timeout;
    private string $lockFilePath;
    public function __construct(int $timeout, string $lockFilePath)
    {
        $this->timeout = $timeout;
        $this->lockFilePath = $lockFilePath;
    }
    public function lock() : bool
    {
        return (bool) \file_put_contents($this->lockFilePath, (string) \time());
    }
    public function unlock() : bool
    {
        if (!\file_exists($this->lockFilePath)) {
            return \true;
        }
        return \unlink($this->lockFilePath);
    }
    public function isLocked() : bool
    {
        $file = $this->lockFilePath;
        if (!\file_exists($file)) {
            return \false;
        }
        $value = \filemtime($file);
        $expiration = \time() - $this->timeout;
        return $value > $expiration;
    }
}
