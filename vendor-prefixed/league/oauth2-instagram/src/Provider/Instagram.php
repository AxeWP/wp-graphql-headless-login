<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider;

use WPGraphQL\Login\Vendor\League\OAuth2\Client\Provider\Exception\InstagramIdentityProviderException;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken;
use WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessTokenInterface;
use WPGraphQL\Login\Vendor\Psr\Http\Message\ResponseInterface;

class Instagram extends AbstractProvider
{
    /**
     * @var string Key used in a token response to identify the resource owner.
     */
    const ACCESS_TOKEN_RESOURCE_OWNER_ID = 'user_id';

    /**
     * Default scopes
     *
     * @var array
     */
    public $defaultScopes = ['user_profile'];

    /**
     * Default host
     *
     * @var string
     */
    protected $host = 'https://api.instagram.com';

    /**
     * Default Graph API host
     *
     * @var string
     */
    protected $graphHost = 'https://graph.instagram.com';

    /**
     * Gets host.
     *
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Gets Graph API host.
     *
     * @return string
     */
    public function getGraphHost()
    {
        return $this->graphHost;
    }

    /**
     * Get the string used to separate scopes.
     *
     * @return string
     */
    protected function getScopeSeparator()
    {
        return ' ';
    }

    /**
     * Get authorization url to begin OAuth flow
     *
     * @return string
     */
    public function getBaseAuthorizationUrl()
    {
        return $this->host.'/oauth/authorize';
    }

    /**
     * Get access token url to retrieve token
     *
     * @param  array $params
     *
     * @return string
     */
    public function getBaseAccessTokenUrl(array $params)
    {
        return $this->host.'/oauth/access_token';
    }

    /**
     * Get access token url to
     *  - exchange short-lived token for a long-lived token
     *  - refresh unexpired long-lived token
     *
     * @param array $params
     * @param string $endPoint
     *
     * @return string
     */
    public function getUpdateAccessTokenUrl(array $params, $endPoint)
    {
        return $this->graphHost.'/'.$endPoint;
    }

    /**
     * Get provider url to fetch user details
     *
     * @param  AccessToken $token
     *
     * @return string
     */
    public function getResourceOwnerDetailsUrl(AccessToken $token)
    {
        return $this->graphHost.'/me?fields=id,username&access_token='.$token;
    }

    /**
     * Returns an authenticated PSR-7 request instance.
     *
     * @param  string $method
     * @param  string $url
     * @param  AccessToken|string $token
     * @param  array $options Any of "headers", "body", and "protocolVersion".
     *
     * @return \WPGraphQL\Login\Vendor\Psr\Http\Message\RequestInterface
     */
    public function getAuthenticatedRequest($method, $url, $token, array $options = [])
    {
        $parsedUrl = parse_url($url);
        $queryString = array();

        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryString);
        }

        if (!isset($queryString['access_token'])) {
            $queryString['access_token'] = (string) $token;
        }

        $url = http_build_url($url, [
            'query' => http_build_query($queryString),
        ]);

        return $this->createRequest($method, $url, null, $options);
    }

    /**
     * Get the default scopes used by this provider.
     *
     * This should not be a complete list of all scopes, but the minimum
     * required for the provider user interface!
     *
     * @return array
     */
    protected function getDefaultScopes()
    {
        return $this->defaultScopes;
    }

    /**
     * Check a provider response for errors.
     *
     * @throws InstagramIdentityProviderException
     * @param  ResponseInterface $response
     * @param  string $data Parsed response data
     * @return void
     */
    protected function checkResponse(ResponseInterface $response, $data)
    {
        // Standard error response format
        if (!empty($data['error'])) {
            throw InstagramIdentityProviderException::clientException($response, $data);
        }

        // OAuthException error response format
        if (!empty($data['error_type'])) {
            throw InstagramIdentityProviderException::oauthException($response, $data);
        }
    }

    /**
     * Exchanges a short-lived access token with a long-lived access-token.
     *
     * @param string|AccessTokenInterface $accessToken
     *
     * @return \WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken
     *
     * @throws InstagramIdentityProviderException
     */
    public function getLongLivedAccessToken($accessToken)
    {
        $params = [
            'client_secret' => $this->clientSecret
        ];

        return $this->getUpdatedAccessToken($accessToken, 'ig_exchange_token', $params);
    }

    /**
     * Refresh a long-lived token
     *
     * @param string|AccessTokenInterface $accessToken
     *
     * @return \WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken
     *
     * @throws InstagramIdentityProviderException
     */
    public function getRefreshedAccessToken($accessToken)
    {
        return $this->getUpdatedAccessToken($accessToken, 'ig_refresh_token');
    }

    /**
     * Update token based on grant type
     *
     * @param string|AccessTokenInterface $accessToken
     * @param string $grant
     * @param array $params
     *
     * @return \WPGraphQL\Login\Vendor\League\OAuth2\Client\Token\AccessToken
     *
     * @throws InstagramIdentityProviderException
     */
    protected function getUpdatedAccessToken($accessToken, $grant, $params = [])
    {
        $verifiedGrant = $this->verifyGrant($grant);

        $params = array_merge([
            'access_token' => (string) $accessToken,
        ], $params);

        $params = $verifiedGrant->prepareRequestParameters($params, []);

        if ($grant === 'ig_exchange_token') {
            $updateEndpoint = 'access_token';
        } elseif ($grant === 'ig_refresh_token') {
            $updateEndpoint = 'refresh_access_token';
        } else {
            throw new \UnexpectedValueException(
                sprintf('Invalid grand type "%s". cannot generate update token url.', $grant)
            );
        }

        $url = $this->getUpdateAccessTokenUrl($params, $updateEndpoint);
        $query = $this->getAccessTokenQuery($params);

        $request = $this->getRequest(self::METHOD_GET, $this->appendQuery($url, $query));
        $response = $this->getParsedResponse($request);

        if (false === is_array($response)) {
            throw new \UnexpectedValueException(
                'Invalid response received from Authorization Server. Expected JSON.'
            );
        }

        $prepared = $this->prepareAccessTokenResponse($response);

        return $this->createAccessToken($prepared, $verifiedGrant);
    }


    /**
     * Generate a user object from a successful user details request.
     *
     * @param array $response
     * @param AccessToken $token
     * @return ResourceOwnerInterface
     */
    protected function createResourceOwner(array $response, AccessToken $token)
    {
        return new InstagramResourceOwner($response);
    }

    /**
     * Sets host.
     *
     * @param string $host
     *
     * @return self
     */
    public function setHost($host)
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Sets Graph API host.
     *
     * @param string $host
     *
     * @return self
     */
    public function setGraphHost($host)
    {
        $this->graphHost = $host;

        return $this;
    }
}
