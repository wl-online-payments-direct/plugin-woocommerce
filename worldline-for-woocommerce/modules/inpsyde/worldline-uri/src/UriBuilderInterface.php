<?php

declare (strict_types=1);
namespace Syde\Vendor\Inpsyde\WorldlineForWoocommerce\Uri;

use Syde\Vendor\Psr\Http\Message\UriInterface;
interface UriBuilderInterface
{
    /**
     * Creates Uri from the array of parts, like returned by parse_url.
     */
    public function createUriFromParts(array $parts): UriInterface;
}
