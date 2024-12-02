# Return page module

The Return Page module enables us to repeatedly check the status of a 
custom-defined logic in the backend while staying on the WooCommerce Thank You page.

## Configurable parameters / services

To change how this module works, you can set up the following services within your module.

### Status

`return_page.{paymentMethodID}.status_checker`

An object implementing the `\Inpsyde\ReturnPage\StatusCheckerInterface` interface
that determines the current payment status, returning one of the `\Inpsyde\ReturnPage\ReturnPageStatus` constants.

### Status updater

`return_page.{paymentMethodID}.status_updater`

An object implementing the `\Inpsyde\ReturnPage\StatusUpdaterInterface` interface.

If provided, it will be executed during the last attempt to retrieve the status before the `retry_count * interval` timeout,
allowing to e.g. perform an API request instead of waiting for a webhook.

### Retry count

`return_page.{paymentMethodID}.retry_count`

This controls how many times javascript can check the backend logic status before it stops.

### Interval

`return_page.{paymentMethodID}.interval`

Time in milliseconds that should pass between backend checks.

### Loading message

`return_page.{paymentMethodID}.message.loading`

The message shown while waiting for the result.

### Success message

`return_page.{paymentMethodID}.message.status.success`

The message added at the top of the order received page content if the backend logic returns `SUCCESS` status.\
Can be empty.

### Failed message

`return_page.{paymentMethodID}.message.status.failed`

The message added at the top of the order received page content if the backend logic returns `FAILED` status.\
Can be empty.

### Pending message

`return_page.{paymentMethodID}.message.status.pending`

The message that appears if the backend logic still returns `PENDING` status after the `retry_count * interval` timeout.

### Message renderer

`return_page.{paymentMethodID}.message.render`

An object implementing the `\Inpsyde\ReturnPage\ReturnPageRenderInterface` interface
rendering the given message.

### Status actions

`return_page.{paymentMethodID}.action.status.{status}`

An object implementing the `\Inpsyde\ReturnPage\StatusActionInterface` interface
executed before rendering the return page with the specified status.

## Example

Add payment method ids in the extension array for a service `return_page.payment_gateways`.

```php
<?php

declare(strict_types=1);

return static function (): array {
    return [
        'return_page.payment_gateways' =>
            static function (array $returnPagePaymentGateways): array {
                $returnPagePaymentGateways[] = 'example-payment-method';
                return $returnPagePaymentGateways;
            },
    ];
};
```

Register the following services:

```php
<?php

declare(strict_types=1);

use Inpsyde\ReturnPage\StatusActionInterface;
use Inpsyde\ReturnPage\WcOrderStatusChecker;
use Psr\Container\ContainerInterface;

return static function (): array {
    return [
        'return_page.example-payment-method.payment_status' =>  static fn(ContainerInterface $c): WcOrderStatusChecker =>
            $c->get('return_page.status_checker.wc_status'),
        'return_page.example-payment-method.retry_count' => static fn(): int => 10,
        'return_page.example-payment-method.interval' => static fn(): int => 1000,
        'return_page.example-payment-method.message.loading' => static fn(): string => 'Please wait...',
        'return_page.example-payment-method.message.status.success' => static fn(): string => 'Additional success info.',
        'return_page.example-payment-method.message.status.failed' => static fn(): string => 'Additional payment failure info.',
        'return_page.example-payment-method.message.status.pending' => static fn(): string => 'Still pending...',
        'return_page.worldline-for-woocommerce.action.status.cancelled' => static fn(ContainerInterface $c): StatusActionInterface =>
            $c->get('return_page.action.pay_order_redirect'),
    ];
};
```
