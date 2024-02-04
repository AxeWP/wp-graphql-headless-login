<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\League\OAuth2\Client\Exception;

/**
 * Exception thrown if the Google Provider is configured with a hosted domain that the user doesn't belong to
 */
class HostedDomainException extends \Exception
{
    /**
     * @param $configuredDomain
     *
     * @return static
     */
    public static function notMatchingDomain($configuredDomain): self
    {
        return new static("User is not part of domain '$configuredDomain'");
    }
}
