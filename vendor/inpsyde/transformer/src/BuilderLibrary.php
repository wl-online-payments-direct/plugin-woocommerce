<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\Transformer;

use Syde\Vendor\Worldline\Dhii\Container\CompositeCachingServiceProvider;
use Syde\Vendor\Worldline\Dhii\Container\DelegatingContainer;
use Syde\Vendor\Worldline\Dhii\Container\ServiceProvider;
use Syde\Vendor\Worldline\Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Syde\Vendor\Worldline\Psr\Container\ContainerInterface;
class BuilderLibrary
{
    private DelegatingContainer $container;
    private CompositeCachingServiceProvider $provider;
    private BuilderModule $module;
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
    public function container() : ContainerInterface
    {
        return $this->container;
    }
}
