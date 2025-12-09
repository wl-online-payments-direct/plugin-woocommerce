<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Uri;

use Syde\Vendor\Worldline\Psr\Http\Message\UriInterface;
interface UriBuilderInterface
{
    /**
     * Creates Uri from the array of parts, like returned by parse_url.
     */
    public function createUriFromParts(array $parts) : UriInterface;
}
