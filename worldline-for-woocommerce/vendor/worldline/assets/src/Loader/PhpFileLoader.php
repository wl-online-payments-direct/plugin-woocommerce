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

use Worldline\Assets\Exception\FileNotFoundException;
/**
 * @package Worldline\Assets\Loader
 */
class PhpFileLoader extends \Worldline\Assets\Loader\ArrayLoader
{
    /**
     * @param mixed $resource      the path to your php-file.
     * @return array
     *
     * phpcs:disable Worldline.CodeQuality.ArgumentTypeDeclaration
     * @psalm-suppress UnresolvableInclude
     */
    public function load($resource) : array
    {
        if (!\is_string($resource) || !\is_readable($resource)) {
            throw new FileNotFoundException(\sprintf('The given file "%s" does not exists or is not readable.', (string) $resource));
        }
        $data = (require $resource);
        return parent::load($data);
    }
}
