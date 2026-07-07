<?php

declare (strict_types=1);
namespace Worldline\Vendor;

/**
 * Plugin Name: ddev-wordpress-plugin-example
 * Plugin URI:  https://worldline.com
 * Description: {DESCRIPTION}
 * Version:     {VERSION}
 * SHA:         ${GIT_SHA}
 * Requires at least: 5.8
 * Requires PHP: 7.2
 * WC requires at least: 4.3
 * WC tested up to: 5.5
 * Author:      Worldline
 * Author URI:  https://worldline.com
 * License:     GPL-2.0
 * Text Domain: ddev-wordpress-plugin-example
 * Domain Path: /languages
 */
\add_action('rest_api_init', static function () {
    \register_rest_route('worldline', 'example', ['method' => 'GET', 'callback' => static function () {
        return ['hello' => \__('world', 'ddev-wordpress-plugin-example')];
    }, 'permission_callback' => '__return_true']);
});
