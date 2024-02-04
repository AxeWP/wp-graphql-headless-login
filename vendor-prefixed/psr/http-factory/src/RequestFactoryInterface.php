<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\Psr\Http\Message;

interface RequestFactoryInterface
{
    /**
     * Create a new request.
     *
     * @param string $method The HTTP method associated with the request.
     * @param UriInterface|string $uri The URI associated with the request. If
     *     the value is a string, the factory MUST create a UriInterface
     *     instance based on it.
     *
     * @return RequestInterface
     */
    public function createRequest(string $method, $uri): RequestInterface;
}
