<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Exception;

use WPGraphQL\Login\Vendor\Psr\Http\Message\ResponseInterface;

class InstagramIdentityProviderException extends IdentityProviderException
{
    /**
     * Creates client exception from response.
     *
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     *
     * @return IdentityProviderException
     */
    public static function clientException(ResponseInterface $response, $data)
    {
        $message = $response->getReasonPhrase();
        $code = $response->getStatusCode();
        $body = (string) $response->getBody();

        if (isset($data['error'], $data['error']['message'])) {
            $message = $data['error']['message'];
        }
        if (isset($data['error'], $data['error']['code'])) {
            $code = $data['error']['code'];
        }

        return new static($message, $code, $body);
    }

    /**
     * Creates oauth exception from response.
     *
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     *
     * @return IdentityProviderException
     */
    public static function oauthException(ResponseInterface $response, $data)
    {
        $message = $response->getReasonPhrase();
        $code = $response->getStatusCode();
        $body = (string) $response->getBody();

        if (isset($data['error_message'])) {
            $message = $data['error_message'];
        }
        if (isset($data['code'])) {
            $code = $data['code'];
        }

        return new static($message, $code, $body);
    }
}
