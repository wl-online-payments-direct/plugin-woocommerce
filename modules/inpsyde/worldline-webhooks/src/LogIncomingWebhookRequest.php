<?php

declare (strict_types=1);
namespace Syde\Vendor\Worldline\Inpsyde\WorldlineForWoocommerce\Webhooks;

use WP_REST_Request;
/**
 * Converts and cleans up webhook requests data to make it suitable for logging.
 */
class LogIncomingWebhookRequest
{
    /**
     * @var string[]
     */
    protected array $securityHeaderNames;
    /**
     * @param string[] $securityHeaderNames
     */
    public function __construct(array $securityHeaderNames)
    {
        $this->securityHeaderNames = \array_map(static function (string $header) : string {
            return WP_REST_Request::canonicalize_header_name($header);
        }, $securityHeaderNames);
    }
    /**
     * @param WP_REST_Request $request
     *
     * @return void
     */
    public function __invoke(WP_REST_Request $request) : void
    {
        /** @var array<string, string[]> $headers */
        $headers = $request->get_headers();
        $headers = $this->redactHeaders($headers);
        $stringifiedHeaders = $headers ? \json_encode($headers) : 'empty';
        $queryParams = $request->get_query_params();
        $stringifiedQueryParams = $queryParams ? \json_encode($queryParams) : 'empty';
        /** @var string|null $body */
        $body = $request->get_body();
        if ($body === '' || $body === null) {
            $body = 'empty';
        }
        \do_action('wlop.incoming_request_data', ['method' => $request->get_method(), 'queryParams' => $stringifiedQueryParams, 'bodyContents' => $body, 'headers' => $stringifiedHeaders]);
    }
    /**
     * Remove sensitive data from headers.
     *
     * @param array<string, string[]> $headers
     *
     * @return array<string, string[]>
     */
    protected function redactHeaders(array $headers) : array
    {
        foreach ($this->securityHeaderNames as $headerName) {
            if (\array_key_exists($headerName, $headers)) {
                $headers[$headerName] = ['***'];
            }
        }
        return $headers;
    }
}
