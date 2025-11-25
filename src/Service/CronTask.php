<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTask;

class CronTask extends ScheduledTask
{
    public const CRON_INTERVAL = 600;

    public static function getTaskName(): string
    {
        return 'worldline.cron_task';
    }

    public static function getDefaultInterval(): int
    {
        return self::CRON_INTERVAL;
    }

    public static function shouldRescheduleOnFailure(): bool
    {
        return false;
    }
}
