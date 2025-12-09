<?php

namespace Syde\Vendor\Worldline\OnlinePayments\Sdk\Communication;

/**
 * Interface MetadataProviderInterface
 *
 * @package OnlinePayments\Sdk\Communication
 */
interface MetadataProviderInterface
{
    /**
     * @return string
     */
    function getServerMetaInfoValue();
}
