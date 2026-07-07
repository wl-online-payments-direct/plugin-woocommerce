<?php

declare (strict_types=1);
/*
 * This file is part of the Assets package.
 *
 * (c) Worldline
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Worldline\Assets\Loader;

interface LoaderInterface
{
    /**
     * @param mixed $resource
     *
     * @return array
     */
    public function load($resource) : array;
}
