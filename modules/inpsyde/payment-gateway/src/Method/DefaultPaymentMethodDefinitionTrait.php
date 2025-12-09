<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\PaymentGateway\Method;

use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\GatewayIconsRendererInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\IconProviderInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\DefaultIconsRenderer;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\NoopPaymentProcessor;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\NoopPaymentRequestValidator;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\NoopRefundProcessor;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentFieldsRendererInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\PaymentRequestValidatorInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\RefundProcessorInterface;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\ServiceKeyGenerator;
use Syde\Vendor\Worldline\Inpsyde\PaymentGateway\StaticIconProvider;
use Syde\Vendor\Worldline\Psr\Container\ContainerExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
use Syde\Vendor\Worldline\Psr\Container\NotFoundExceptionInterface;
/**
 * Trait providing default implementations for PaymentMethodDefinition interface methods.
 */
trait DefaultPaymentMethodDefinitionTrait
{
    private function ensureServiceKeyGenerator() : ServiceKeyGenerator
    {
        static $keyGen;
        if (!$keyGen) {
            $keyGen = new ServiceKeyGenerator($this->id());
        }
        return $keyGen;
    }
    public function paymentProcessor(ContainerInterface $container) : PaymentProcessorInterface
    {
        return new NoopPaymentProcessor();
    }
    public function paymentRequestValidator(ContainerInterface $container) : PaymentRequestValidatorInterface
    {
        return new NoopPaymentRequestValidator();
    }
    public function title(ContainerInterface $container) : string
    {
        return $this->id();
    }
    public function methodTitle(ContainerInterface $container) : string
    {
        return $this->id();
    }
    public function description(ContainerInterface $container) : string
    {
        return $this->id();
    }
    public function methodDescription(ContainerInterface $container) : string
    {
        return $this->id();
    }
    public function availabilityCallback(ContainerInterface $container) : callable
    {
        return static fn() => \true;
    }
    public function supports(ContainerInterface $container) : array
    {
        return ['products'];
    }
    public function refundProcessor(ContainerInterface $container) : RefundProcessorInterface
    {
        return new NoopRefundProcessor();
    }
    public function paymentMethodIconProvider(ContainerInterface $container) : IconProviderInterface
    {
        return new StaticIconProvider();
    }
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function gatewayIconsRenderer(ContainerInterface $container) : GatewayIconsRendererInterface
    {
        try {
            $iconProvider = $container->get($this->ensureServiceKeyGenerator()->createKey('method_icon_provider'));
        } catch (NotFoundExceptionInterface|ContainerExceptionInterface $exception) {
            $iconProvider = $container->get($this->ensureServiceKeyGenerator()->createFallbackKey('method_icon_provider'));
        }
        \assert($iconProvider instanceof IconProviderInterface);
        return new DefaultIconsRenderer($iconProvider);
    }
    public function paymentFieldsRenderer(ContainerInterface $container) : PaymentFieldsRendererInterface
    {
        /**
         * Trigger fallback within PaymentGateway by pretending the service does not exist
         */
        throw new class("Method 'paymentFieldsRenderer' not implemented.") extends \Exception implements NotFoundExceptionInterface
        {
        };
    }
    public function hasFields(ContainerInterface $container) : bool
    {
        return \false;
    }
    public function formFields(ContainerInterface $container) : array
    {
        //TODO i18n
        return ['enabled' => ['title' => 'Enable/Disable', 'type' => 'checkbox', 'label' => 'Enable payment method', 'default' => 'no']];
    }
    public function optionKey(ContainerInterface $container) : string
    {
        /**
         * Trigger fallback within PaymentGateway by pretending the service does not exist
         */
        throw new class("Method 'optionKey' not implemented.") extends \Exception implements NotFoundExceptionInterface
        {
        };
    }
    public function registerBlocks(ContainerInterface $container) : bool
    {
        return \true;
    }
    public function orderButtonText(ContainerInterface $container) : ?string
    {
        return null;
    }
    public function customSettings() : CustomSettingsFieldsDefinition
    {
        return new CustomSettingsFields([], []);
    }
}
