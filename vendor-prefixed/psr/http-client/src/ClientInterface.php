<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\Psr\Http\Client;

use WPGraphQL\Login\Vendor\Psr\Http\Message\RequestInterface;
use WPGraphQL\Login\Vendor\Psr\Http\Message\ResponseInterface;

interface ClientInterface
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \WPGraphQL\Login\Vendor\Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface;
}
