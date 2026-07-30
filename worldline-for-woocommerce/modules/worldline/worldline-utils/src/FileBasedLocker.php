<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Utils;

class FileBasedLocker implements LockerInterface
{
    private int $timeout;
    private string $lockFilePath;
    /** @var resource|null */
    private $handle;
    public function __construct(int $timeout, string $lockFilePath)
    {
        $this->timeout = $timeout;
        $this->lockFilePath = $lockFilePath;
    }
    /**
     * Acquiring the lock is a single flock() syscall, so two requests can
     * no longer both see "not locked" before either has locked: exactly
     * one of them gets true.
     */
    public function lock() : bool
    {
        $handle = \fopen($this->lockFilePath, 'c');
        if ($handle === \false) {
            return \false;
        }
        if (!\flock($handle, \LOCK_EX | \LOCK_NB)) {
            \fclose($handle);
            return \false;
        }
        \ftruncate($handle, 0);
        \fwrite($handle, (string) \time());
        \fflush($handle);
        $this->handle = $handle;
        return \true;
    }
    public function lockBlocking() : bool
    {
        $deadline = \microtime(\true) + $this->timeout;
        while (!$this->lock()) {
            if (\microtime(\true) >= $deadline) {
                return \false;
            }
            \usleep(100000);
        }
        return \true;
    }
    public function unlock() : bool
    {
        if ($this->handle === null) {
            return \true;
        }
        \flock($this->handle, \LOCK_UN);
        \fclose($this->handle);
        $this->handle = null;
        // Intentionally not deleted: unlinking a still-flock()-able path
        // would let a concurrent lock() recreate and lock a different
        // inode than the one this process opened, reintroducing a race.
        return \true;
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
