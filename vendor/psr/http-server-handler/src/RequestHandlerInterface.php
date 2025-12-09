<?php

namespace Syde\Vendor\Worldline\Psr\Http\Server;

use Syde\Vendor\Worldline\Psr\Http\Message\ResponseInterface;
use Syde\Vendor\Worldline\Psr\Http\Message\ServerRequestInterface;
/**
 * Handles a server request and produces a response.
 *
 * An HTTP request handler process an HTTP request in order to produce an
 * HTTP response.
 */
interface RequestHandlerInterface
{
    /**
     * Handles a request and produces a response.
     *
     * May call other collaborating code to generate the response.
     */
    public function handle(ServerRequestInterface $request) : ResponseInterface;
}
