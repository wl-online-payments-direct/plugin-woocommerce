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
namespace Worldline\Assets\OutputFilter;

use Worldline\Assets\Asset;
/**
 * @deprecated use Asset::withAttributes(['async' => true']);
 */
class AsyncScriptOutputFilter implements \Worldline\Assets\OutputFilter\AssetOutputFilter
{
    public function __invoke(string $html, Asset $asset) : string
    {
        return \str_replace('<script ', '<script async ', $html);
    }
}
