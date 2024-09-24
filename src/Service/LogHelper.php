<?php declare(strict_types=1);

/**
 * @author Mediaopt GmbH
 * @package MoptWorldline\Service
 */

namespace MoptWorldline\Service;

use Monolog\Level;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use MoptWorldline\Adapter\WorldlineSDKAdapter;
use MoptWorldline\Bootstrap\Form;
use Symfony\Contracts\Translation\TranslatorInterface;
use Composer\Package\Archiver\ZipArchiver;

class LogHelper
{
    private WorldlineSDKAdapter $adapter;
    private TranslatorInterface $translator;

    private const DEFAULT_LOG_LEVEL = 'INFO';

    /**
     * @param WorldlineSDKAdapter $adapter
     */
    public function __construct(WorldlineSDKAdapter $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param TranslatorInterface $translator
     * @return void
     */
    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    /**
     * @param string $message
     * @param int|Level $logLevel
     * @param mixed $additionalData
     * @return void
     */
    public function log(string $message, int|Level $logLevel = 0, mixed $additionalData = ''): void
    {
        if ($logLevel == 0) {
            $logLevel = $this->getLogLevel();
        }

        self::addLog($logLevel, $message, $additionalData);
    }

    /**
     * @param string $orderNumber
     * @param string $string
     * @param int|Level $logLevel
     * @param mixed|null $additionalData
     * @return void
     */
    public function paymentLog(string $orderNumber, string $string, int|Level $logLevel = 0, mixed $additionalData = null): void
    {
        $additionalData = array_merge([$additionalData], ['orderNumber' => $orderNumber]);
        $this->log(AdminTranslate::trans($this->translator->getLocale(), $string), $logLevel, $additionalData);
    }

    /**
     * get monolog log-level by module configuration
     * @return Level
     */
    private function getLogLevel(): Level
    {
        $logLevel = self::DEFAULT_LOG_LEVEL;

        if ($overrideLogLevel = $this->adapter->getPluginConfig(Form::LOG_LEVEL)) {
            $logLevel = $overrideLogLevel;
        }

        //set levels
        return match ($logLevel) {
            'INFO' => Level::Info,
            'ERROR' => Level::Error,
            'DEBUG' => Level::Debug
        };
    }

    /**
     * @param int|Level $logLevel
     * @param string $message
     * @param mixed $additionalData
     * @return void
     */
    public static function addLog(int|Level $logLevel, string $message, mixed $additionalData = ''): void
    {
        $logger = new Logger('Worldline');
        self::addRecord($logger, $logLevel, $message, $additionalData);
        self::addPluginLog($logLevel, $message, $additionalData);
    }

    /**
     * @param int|Level $logLevel
     * @param string $message
     * @param mixed $additionalData
     * @return void
     */
    public static function addPluginLog(int|Level $logLevel, string $message, mixed $additionalData = ''): void
    {
        $fullPath = dirname(__DIR__, 5) . Form::LOG_DIR_PATH . 'log';
        $streamHandler = new RotatingFileHandler($fullPath, Form::LOG_FILE_MAX, $logLevel);
        $logger = new Logger('worldline', [$streamHandler]);
        self::addRecord($logger, $logLevel, $message, $additionalData);
    }

    /**
     * @param Logger $logger
     * @param int|Level $logLevel
     * @param string $message
     * @param mixed $additionalData
     * @return void
     */
    public static function addRecord(Logger $logger, int|Level $logLevel, string $message, mixed $additionalData = '')
    {
        $logger->addRecord(
            $logLevel,
            $message,
            [
                'source' => 'Worldline',
                'additionalData' => json_encode($additionalData),
            ]
        );
    }

    /**
     * @return string
     */
    public static function getArchive(): string
    {
        $zip = new ZipArchiver();
        $archivePath = dirname(__DIR__, 5) . Form::LOG_ARCHIVE_PATH;
        $zip->archive(
            dirname(__DIR__, 5) . Form::LOG_DIR_PATH,
            $archivePath,
            'zip'
        );

        return $archivePath;
    }
}