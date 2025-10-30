<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks\Controller;

use WP_REST_Request;
use WP_REST_Response;
/**
 * A service accepting API requests and returns responses.
 */
interface WpRestApiControllerInterface
{
    /**
     * @param WP_REST_Request $request
     *
     * @return WP_REST_Response
     */
    public function handleWpRestRequest(WP_REST_Request $request) : WP_REST_Response;
}
