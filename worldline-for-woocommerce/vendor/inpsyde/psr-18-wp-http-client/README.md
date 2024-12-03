# PSR-18 WordPress HTTP Client
[![Continuous Integration](https://github.com/inpsyde/psr-18-wp-http-client/actions/workflows/continuous-integration.yml/badge.svg)](https://github.com/inpsyde/psr-18-wp-http-client/actions/workflows/continuous-integration.yml)

An implementation of the [PSR-18][] standard that wraps WordPress HTTP request functionality.

## Table Of Contents

* [Getting Started](#getting-started)
    * [Prerequisites](#prerequisites)
    * [Installing](#installing)
    * [Usage](#usage)
* [Limitations](#limitations)
* [License and Copyright](#license-and-copyright)

## Getting Started

### Prerequisites

This readme assumes you already have the composer package with your project.

Please, note this implementation does not includes PSR-7 (HTTP Message) and PSR-17 (HTTP Factory) implementations. So, you as the consumer, have to provide respective instances via Client constructor. You can use any existing implementation of [Message] and [Factory] you want to.

### Installing

Require this package in your project using composer:

``` composer require inpsyde/psr-18-wp-http-client ```

### Usage

After installing, you only need  Client instance to start sending requests.

Pass client options as associative array to Client constructor as last argument. Options are the same as WP_Http::request() takes (see [WP_Http::request() params]), except *httpversion* and *blocking*. Please, read Limitations section below for details.

## Limitations
* Since this client uses WordPress classes and functions to send actual requests, it relies on WordPress HTTP Transports ([cURL] or [sockets]).
This client doesn't give you an opportunity to choose which one will be used.

* Although WP_Http::request() method supports HTTP 1.1, this client can use HTTP 1.0 only for now. 
* Although WP_Http::request() method supports asynchronous requests, this client can send only usual blocking requests for now.
* Since PSR-18 compatible client [MUST throw NetworkExceptionInterface] on every network-related failure, this client did it to when Wp_Http::response() returns WP_Error instead of response array. In most cases it happens because of some network problems, as it must be. But it's possible NetworkExceptionInterface will be thrown in case of other problems. This may happen because it's extremely hard to detect fail reason using WordPress internal methods. Hopefully, we'll find some way around in future.

## License and Copyright
Copyright (C) 2020 Inpsyde GmbH.

This project is licensed under this License - see the [LICENSE](LICENSE) file for details


[PSR-18]: https://www.php-fig.org/psr/psr-18/
[Message]: https://packagist.org/providers/psr/http-message-implementation
[Factory]: https://packagist.org/providers/psr/http-factory-implementation
[cURL]: https://github.com/WordPress/WordPress/blob/master/wp-includes/Requests/Transport/cURL.php
[sockets]: https://github.com/WordPress/WordPress/blob/master/wp-includes/Requests/Transport/fsockopen.php
[MUST throw NetworkExceptionInterface]: https://www.php-fig.org/psr/psr-18/#error-handling
[WP_Http::request() params]: https://github.com/WordPress/WordPress/blob/master/wp-includes/class-http.php#L96-L149
