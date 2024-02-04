<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\League\OAuth2\Client\Token;

class LinkedInAccessToken extends AccessToken
{
    /**
     * @var int
     */
    protected $refreshTokenExpires;

    /**
     * Constructs an access token.
     *
     * @param array $options An array of options returned by the service provider
     *     in the access token request. The `access_token` option is required.
     * @throws InvalidArgumentException if `access_token` is not provided in `$options`.
     */
    public function __construct(array $options = [])
    {
        parent::__construct($options);

        if (isset($options['refresh_token_expires_in'])) {
            $expires = $options['refresh_token_expires_in'];
            if (!$this->isExpirationTimestamp($expires)) {
                $expires += time();
            }
            $this->refreshTokenExpires = $expires;
        }
    }

    /**
     * Returns the refresh token expiration timestamp, if defined.
     *
     * @return integer|null
     */
    public function getRefreshTokenExpires()
    {
        return $this->refreshTokenExpires;
    }
}
