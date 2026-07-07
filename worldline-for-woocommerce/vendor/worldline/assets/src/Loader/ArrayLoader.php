<?php

/*
 * This file is part of the Assets package.
 *
 * (c) Worldline
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
declare (strict_types=1);
namespace Worldline\Assets\Loader;

use Worldline\Assets\AssetFactory;
use Worldline\Assets\Asset;
use Worldline\Assets\BaseAsset;
use Worldline\Assets\ConfigureAutodiscoverVersionTrait;
/**
 * @package Worldline\Assets\Loader
 */
class ArrayLoader implements \Worldline\Assets\Loader\LoaderInterface
{
    use ConfigureAutodiscoverVersionTrait;
    /**
     * @param mixed $resource
     *
     * @return array
     *
     * phpcs:disable Worldline.CodeQuality.ArgumentTypeDeclaration
     * @psalm-suppress MixedArgument
     */
    public function load($resource) : array
    {
        $assets = \array_map([AssetFactory::class, 'create'], (array) $resource);
        return \array_map(function (Asset $asset) : Asset {
            if ($asset instanceof BaseAsset) {
                $this->autodiscoverVersion ? $asset->enableAutodiscoverVersion() : $asset->disableAutodiscoverVersion();
            }
            return $asset;
        }, $assets);
    }
}
