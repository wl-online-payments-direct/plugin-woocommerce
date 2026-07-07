<?php

declare (strict_types=1);
namespace Worldline\Vendor\Worldline\Transformer;

use Worldline\Vendor\Dhii\Container\CompositeCachingServiceProvider;
use Worldline\Vendor\Dhii\Container\DelegatingContainer;
use Worldline\Vendor\Dhii\Container\ServiceProvider;
use Worldline\Vendor\Dhii\Modular\Module\Exception\ModuleExceptionInterface;
use Worldline\Vendor\Psr\Container\ContainerInterface;
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
