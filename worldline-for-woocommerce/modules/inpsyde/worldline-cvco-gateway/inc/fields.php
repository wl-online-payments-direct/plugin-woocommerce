<?php

declare(strict_types=1);

// phpcs:disable Inpsyde.CodeQuality.LineLength.TooLong

use Syde\Vendor\Worldline\Dhii\Services\Factory;

return new Factory(
    [],
    static function (): array {
        return array_merge(
            [
                'enabled' => [
                    'title' => __('Enable/Disable', 'worldline-for-woocommerce'),
                    'type' => 'checkbox',
                    'label' => __('Enable Cheque Vacances Connect (Worldline)', 'worldline-for-woocommerce'),
                    'default' => 'no',
                ],
            ],
        );
    }
);
