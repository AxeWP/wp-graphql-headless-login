<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider;

class AppSecretProof
{
    /**
     * The app secret proof to sign requests made to the Graph API
     * @see https://developers.facebook.com/docs/graph-api/securing-requests#appsecret_proof
     *
     * @param string $appSecret
     * @param string $accessToken
     * @return string
     */
    public static function create(string $appSecret, string $accessToken): string
    {
        return hash_hmac('sha256', $accessToken, $appSecret);
    }
}
