<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Logger;

use Syde\Vendor\Worldline\Inpsyde\Logger\Events\EventDispatcherInterface;
use Syde\Vendor\Worldline\Inpsyde\Logger\Events\WpEventDispatcher;
use Syde\Vendor\Worldline\Inpsyde\Logger\Events\HandlerAdderInterface;
use Syde\Vendor\Worldline\Inpsyde\Logger\Events\WpHandlerAdder;
use Syde\Vendor\Worldline\Inpsyde\Logger\Exception\LoggerException;
use Syde\Vendor\Worldline\Inpsyde\Logger\Formatter\DelegatingObjectFormatter;
use Syde\Vendor\Worldline\Inpsyde\Logger\Formatter\ExceptionFormatter;
use Syde\Vendor\Worldline\Inpsyde\Logger\Formatter\ObjectFormatter;
use Syde\Vendor\Worldline\Inpsyde\Logger\Formatter\ObjectFormatterInterface;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ExecutableModule;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ModuleClassNameIdTrait;
use Syde\Vendor\Worldline\Inpsyde\Modularity\Module\ServiceModule;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Log\LoggerInterface;
use Syde\Vendor\Worldline\Psr\Log\LogLevel;
use Throwable;
use UnexpectedValueException;
/**
 * The main module class.
 * phpcs:disable Inpsyde.CodeQuality.FunctionLength.TooLong
 */
class LoggerModule implements ServiceModule, ExecutableModule
{
    use ModuleClassNameIdTrait;
    public function run(ContainerInterface $container) : bool
    {
        $autoRegistrationEnabled = $container->get('inpsyde_logger.auto_register_logging_events');
        if ($autoRegistrationEnabled) {
            $registerLoggingEvents = $container->get('inpsyde_logger.register_logging_events');
            \assert(\is_callable($registerLoggingEvents));
            $registerLoggingEvents();
        }
        return \true;
    }
    /**
     * @inheritDoc
     */
    public function services() : array
    {
        return ['inpsyde_logger.logger' => static function (ContainerInterface $container) : LoggerInterface {
            return $container->get('inpsyde_logger.native_php_logger');
        }, 'inpsyde_logger.logging_source' => static function () : string {
            return '';
        }, 'inpsyde_logger.register_logging_events' => function (ContainerInterface $container) : callable {
            return function () use($container) : void {
                $defaultLogLevel = $container->get('inpsyde_logger.default_log_level');
                /** @var HandlerAdderInterface $handlerAdder */
                $handlerAdder = $container->get('inpsyde_logger.handler_adder');
                $eventDispatcher = $container->get('inpsyde_logger.event_dispatcher');
                /** @var string $loggingFailedEventName */
                $loggingFailedEventName = $container->get('inpsyde_logger.logging_failed_event_name');
                foreach ($container->get('inpsyde_logger.log_events') as $logEventConfig) {
                    $eventName = $this->getEventNameFromConfig($logEventConfig);
                    $handlerAdder->addHandler(
                        $eventName,
                        /**
                         * @param mixed $context
                         *
                         * @throws LoggerException
                         */
                        function ($context = []) use($defaultLogLevel, $logEventConfig, $eventDispatcher, $loggingFailedEventName, $container) : void {
                            $context = \is_array($context) ? $context : [];
                            /**
                             * @var LoggerInterface $logger
                             */
                            $logger = $container->get('inpsyde_logger.logger');
                            /** @var bool $isDebug */
                            $isDebug = $container->get('inpsyde_logger.is_debug');
                            $this->processLoggingEvent($defaultLogLevel, $logEventConfig, $context, $logger, $eventDispatcher, $loggingFailedEventName, $isDebug);
                        },
                        10
                    );
                }
            };
        }, 'inpsyde_logger.native_php_logger' => static function (ContainerInterface $container) : LoggerInterface {
            $formatter = $container->get('inpsyde_logger.object_formatter');
            $source = $container->get('inpsyde_logger.logging_source');
            $version = $container->get('properties')->version();
            $isDebug = $container->get('inpsyde_logger.is_debug');
            return new NativePhpLogger($formatter, $source, $version, $isDebug);
        }, 'inpsyde_logger.wc_logger' => static function (ContainerInterface $container) : LoggerInterface {
            $formatter = $container->get('inpsyde_logger.object_formatter');
            $source = $container->get('inpsyde_logger.logging_source');
            $version = $container->get('properties')->version();
            $nativeWcLogger = $container->get('inpsyde_logger.native_wc_logger');
            $isDebug = $container->get('inpsyde_logger.is_debug');
            return new PsrWcLogger($formatter, $nativeWcLogger, $source, $version, $isDebug);
        }, 'inpsyde_logger.query_monitor_logger' => static function (ContainerInterface $container) : LoggerInterface {
            $formatter = $container->get('inpsyde_logger.object_formatter');
            $source = $container->get('inpsyde_logger.logging_source');
            $version = $container->get('properties')->version();
            $isDebug = $container->get('inpsyde_logger.is_debug');
            return new QueryMonitorLogger($formatter, $source, $version, $isDebug);
        }, 'inpsyde_logger.handler_adder' => static function () : HandlerAdderInterface {
            return new WpHandlerAdder();
        }, 'inpsyde_logger.event_dispatcher' => static function () : EventDispatcherInterface {
            return new WpEventDispatcher();
        }, 'inpsyde_logger.default_log_level' => static function () : string {
            return LogLevel::ERROR;
        }, 'inpsyde_logger.log_events' => static function () : iterable {
            return [];
        }, 'inpsyde_logger.logging_failed_event_name' => static function () : string {
            return 'inpsyde_logger.logging_failed';
        }, 'inpsyde_logger.is_debug' => static function () : bool {
            return \true;
        }, 'inpsyde_logger.log_exception_backtrace' => static function (ContainerInterface $container) : bool {
            return (bool) $container->get('inpsyde_logger.is_debug');
        }, 'inpsyde_logger.auto_register_logging_events' => static function () : bool {
            return \true;
        }, 'inpsyde_logger.object_formatter.map.exception' => static function (ContainerInterface $container) : ObjectFormatterInterface {
            return new ExceptionFormatter((bool) $container->get('inpsyde_logger.log_exception_backtrace'));
        }, 'inpsyde_logger.object_formatter.map' => static function (ContainerInterface $container) : array {
            return [Throwable::class => $container->get('inpsyde_logger.object_formatter.map.exception')];
        }, 'inpsyde_logger.object_formatter' => static function (ContainerInterface $container) : ObjectFormatterInterface {
            /** @var ObjectFormatterInterface[] $map */
            $map = $container->get('inpsyde_logger.object_formatter.map');
            return new DelegatingObjectFormatter($map, new ObjectFormatter());
        }];
    }
    /**
     * @param string $defaultLogLevel
     * @param array{
     *     name: string,
     *     context: array,
     *     log_level: string,
     *     message: string|callable(array):string
     * } $logEventConfig
     * @param array $context
     * @param LoggerInterface $logger
     * @param EventDispatcherInterface $eventDispatcher
     * @param string $loggingFailedEventName
     * @param bool $isDebug
     *
     * @throws LoggerException
     */
    protected function processLoggingEvent(string $defaultLogLevel, array $logEventConfig, array $context, LoggerInterface $logger, EventDispatcherInterface $eventDispatcher, string $loggingFailedEventName, bool $isDebug) : void
    {
        $contextConfig = $logEventConfig['context'] ?? [];
        $logLevel = $logEventConfig['log_level'] ?? $defaultLogLevel;
        $message = $logEventConfig['message'] ?? '';
        try {
            $context = \array_merge($contextConfig, $context);
            $message = $this->normalizeMessage($message, $context);
            $logger->log($logLevel, $message, $context);
        } catch (Throwable $throwable) {
            //Give application a chance to do something about failed log attempt
            $this->dispatchLoggingFailedEvent($eventDispatcher, $throwable, $loggingFailedEventName, $context, $logLevel, \is_string($message) ? $message : null);
            if ($isDebug) {
                throw new LoggerException('Failed to add log entry.', 0, $throwable);
            }
        }
    }
    /**
     * @param mixed $message
     * @param array $context
     *
     * @return string
     *
     * @throws UnexpectedValueException If cannot get a string message.
     */
    protected function normalizeMessage($message, array $context) : string
    {
        if (\is_string($message)) {
            return $message;
        }
        if (\is_callable($message)) {
            $result = $message($context);
            if (\is_string($result)) {
                return $result;
            }
            $type = \gettype($result);
            throw new UnexpectedValueException(\sprintf('Log message callback must return string, %1$s returned.', $type));
        }
        $messageType = \gettype($message);
        throw new UnexpectedValueException(\sprintf('Log message must be either string or callable returning string, %1$s provided.', $messageType));
    }
    /**
     * Dispatch a failed log event.
     *
     * @param EventDispatcherInterface $eventDispatcher
     * @param Throwable $throwable
     * @param string $loggingFailedEventName
     * @param array $context
     * @param string|null $logLevel
     * @param string|null $message
     */
    protected function dispatchLoggingFailedEvent(EventDispatcherInterface $eventDispatcher, Throwable $throwable, string $loggingFailedEventName, array $context, string $logLevel = null, string $message = null) : void
    {
        $eventDispatcher->dispatch($loggingFailedEventName, [['log_level' => $logLevel, 'message' => $message, 'context' => $context], $throwable]);
    }
    /**
     * Get event name from config or return empty string.
     *
     * @param array{name: string, context: array, log_level: string, message: string} $config
     *
     * @return string Non-empty event name string.
     *
     * @throws LoggerException
     */
    protected function getEventNameFromConfig(array $config) : string
    {
        $eventName = $config['name'] ?? '';
        \settype($eventName, 'string');
        if (!\is_string($eventName) || $eventName === '') {
            throw new LoggerException(\sprintf('Invalid log event name received from config. Expected non-empty string, got %1$s', \var_export($config['name'] ?? 'null', \true)));
        }
        return $eventName;
    }
}
