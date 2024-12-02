<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\Transformer;

use Syde\Vendor\Dhii\Container\CompositeCachingServiceProvider;
use Syde\Vendor\Dhii\Container\DelegatingContainer;
use Syde\Vendor\Dhii\Container\ServiceProvider;
use Syde\Vendor\Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Syde\Vendor\Psr\Container\ContainerInterface;
class BuilderLibrary
{
    /**
     * @var DelegatingContainer
     */
    private $container;
    /**
     * @var CompositeCachingServiceProvider
     */
    private $provider;
    /**
     * @var BuilderModule
     */
    private $module;
    /**
     * QueueLibrary constructor.
     *
     * @param array $factories
     * @param array $extensions
     *
     * @throws ModuleExceptionInterface
     */
    public function __construct(array $factories = [], array $extensions = [])
    {
        $this->module = new BuilderModule();
        $providers = [$this->module->setup()];
        $providers[] = new ServiceProvider($factories, $extensions);
        $this->provider = new CompositeCachingServiceProvider($providers);
        $this->container = new DelegatingContainer($this->provider);
    }
    /**
     * @throws ModuleExceptionInterface
     */
    public function initialize()
    {
        $this->module->run($this->container());
    }
    public function container(): ContainerInterface
    {
        return $this->container;
    }
}
