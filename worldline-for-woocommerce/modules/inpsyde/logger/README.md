# Inpsyde Logger
[![Continuous Integration](https://github.com/inpsyde/logger/actions/workflows/ci.yml/badge.svg)](https://github.com/inpsyde/inpsyde-woocommerce-logging/actions/workflows/ci.yml)

A module for [inpsyde/modularity][] providing tools for logging. Heavily based on [inpsyde/product-woocommerce-logging](https://github.com/inpsyde/product-woocommerce-logging).

This module allows you to define the list of events that will be logged. As a default logger used NativePhpLogger class,
but it can be replaced with any other implementing `Psr\Log\LoggerInterface`. Additionally, provided PsrWcLogger class.
It's based on WooCommerce logging system.

## Installation

The best way to install this package is through Composer:

```BASH
$ composer require inpsyde/logger
```

## Usage

There are two options of package usage: event-based and direct.

### Event-based usage
***

Extend the `inpsyde_logger.log_events` service in your application like this:

```php
//my-plugin.php
'inpsyde_logger.log_events' => function (array $existingLogEvents, \Psr\Container\ContainerInterface $container): array {
    $logEventsToAdd = [
        [
            'name' => 'my_app_something_failed',
            'log_level' => \Psr\Log\LogLevel::ERROR,
            'message' => 'Failed to do action, exception caught: {exception}',
        ],
        [
            'name' => 'my_app_something_happened',
            'log_level' => \Psr\Log\LogLevel::INFO,
            'message' => 'Webhook call was received'
        ],
                [
            'name' => 'my_app_product_added',
            'log_level' => \Psr\Log\LogLevel::NOTICE,
            'message' => function($context): string {
                $product = $context['product'];
            
                return sprintf('Product with ID %1$s was added.', $product->id());
            }
        ],
    ];
    
    return array_merge($existingLogEvents, $logEventsToAdd);
}
```
Note that `message` may be either string or a callable returning string. You may provide your function returning log message
if you want more control of the actual message producing in runtime.

Then, dispatch an event with data required for logging:

```php
//SomePluginClass.php
do_action('my_app_something_failed', ['exception' => $exception]);
```

or

```php
/** @var \Psr\EventDispatcher\EventDispatcherInterface $eventDispatcher */
$eventDispatcher->dispatch((object) ['name' => 'my_app_something_failed', 'exception' => $exception]);
```

### Direct usage of Logger
***
You can use the logger directly, like this:

```php
    /** @var \Psr\Log\LoggerInterface $logger */
    $logger = $container->get('inpsyde_logger.logger');
    $logger->info('Hi! This is the log message with {your-placeholder}.', ['your-placeholder' => 'placeholder content']);
```

### Adding context
***
As you may notice, both usage examples included context array with placeholder, and its actual value.
Context array is optional in both cases. Both will produce the same log entry:
```
Hi! This is the log message with placeholder content.
```

If you want to, you can set default source of logs, so it can be added to each entry if none provided per request. To do so, declare service `inpsyde_logger.log_events`.
For example:
```php
//PluginCore.php
class PluginCore implements \Inpsyde\Modularity\Module\ExtendingModule {
    public function extensions() : array{
        return [
            //your core module services here
            'inpsyde_logger.logging_source' => 'My awesome plugin name'
        ];
    }
}
```

Default source of logs can be overridden for any log entry by adding `source` element to your `context` array like this:
```php
// If using PsrWcLogger source from the context will be used automatically.
$logger->info(
        'Hi! This is the log message with {your-placeholder}.',
        [
            'your-placeholder' => 'placeholder content',
            'source' => 'My app'
        ]
    );
```

Adding `source` to the `context` without respective placeholder in the message will work if you are using 
`PsrWcLogger` (default) as a logger class. Otherwise, you need to add `source` as a placeholder with some source as a value.
For example:
```php
    //If using logger class other than PsrWcLogger:
    $logger->info(
        '{source}: Hi! This is the log message with {your-placeholder}.',
        [
            'your-placeholder' => 'placeholder content',
            'source' => 'My app'
        ]
    );
```

### Logging complex data

Using the variable interpolation mechanisms described above works well enough for scalar data and `stringable` objects, but has some pitfalls with
more complex objects. 

```php
assert($wcOrder instanceof WC_Order);
$logger->info('Failed to create payload from order: {order}', ['order' => $wcOrder]);
```
You would not usually want to bloat up your business logic with the peculiarities of turning a `WC_Order` object into a meaningful yet compact
log message. At the same time, the logging module cannot possibly be aware of every object that exists in your application.
To solve this problem, the module can be configured with object formatters. It uses a map to determine which formatter to use for a given object type:

```php
            'inpsyde_logger.object_formatter.map' =>
                static function (ContainerInterface $container): array {
                    return [
                        Throwable::class => $container->get('inpsyde_logger.object_formatter.map.exception'),
                    ];
                },
```
Via service extensions, you can add formatters for any object type.
If none is found, a default is used that checks if there is a `__toString` method.

## Development

1. Run `make setup` to setup Docker and install dependencies.
2. Run `make lint test` to run linter and tests.

See [Makefile](/Makefile) for other useful commands.

The [.env](/.env.example) file contains some configuration of the Docker environment.
You may need to rebuild Docker for changes (like WP version) to take effect: `make destroy setup` (all WP data will be lost). 

For Windows users: `make` is not included out-of-the-box but you can simply copy the commands from [Makefile](/Makefile) to `cmd`,
e.g. `docker-compose run --rm test vendor/bin/phpunit` instead of `make test`.

## Crafted by Inpsyde

The team at [Inpsyde][] is engineering the Web since 2006.

## License

This module is provided under the [GPLv2](https://opensource.org/licenses/gpl-2.0.php) license.


## Contributing

All feedback / bug reports / pull requests are welcome.

[inpsyde/modularity]: https://github.com/inpsyde/modularity
[wp-events]: https://github.com/inpsyde/wp-events
[container-interop/service-provider]: https://github.com/container-interop/service-provider
[Inpsyde]: https://inpsyde.com
