<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\League\OAuth2\Client\Grant;

class FbExchangeToken extends AbstractGrant
{
    public function __toString(): string
    {
        return 'fb_exchange_token';
    }

    protected function getRequiredRequestParameters(): array
    {
        return [
            'fb_exchange_token',
        ];
    }

    protected function getName(): string
    {
        return 'fb_exchange_token';
    }
}
