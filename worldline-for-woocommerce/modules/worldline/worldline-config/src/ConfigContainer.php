<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\WorldlineForWoocommerce\Config;

use Exception;
use Worldline\Vendor\Worldline\PaymentGateway\PaymentGateway;
use Worldline\Vendor\Psr\Container\ContainerInterface;
use Worldline\Vendor\Psr\Container\NotFoundExceptionInterface;
/**
 * The wrapper for reading/writing the gateway settings.
 */
class ConfigContainer implements ContainerInterface
{
    protected PaymentGateway $gateway;
    public function __construct(PaymentGateway $gateway)
    {
        $this->gateway = $gateway;
    }
    public function get(string $id)
    {
        $result = $this->gateway->get_option($id);
        if ($result === null) {
            throw new class("Option with key {$id} is not found in the gateway {$this->gateway->id}.") extends Exception implements NotFoundExceptionInterface
            {
            };
        }
        return $result;
    }
    public function has(string $id)
    {
        /**
         * @psalm-suppress RedundantConditionGivenDocblockType
         */
        return $this->gateway->get_option($id) !== null;
    }
    public function set(string $id, $value) : void
    {
        $this->gateway->update_option($id, $value);
    }
}
