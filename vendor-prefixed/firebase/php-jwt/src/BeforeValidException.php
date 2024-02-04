<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */

namespace WPGraphQL\Login\Vendor\Firebase\JWT;

class BeforeValidException extends \UnexpectedValueException implements JWTExceptionWithPayloadInterface
{
    private object $payload;

    public function setPayload(object $payload): void
    {
        $this->payload = $payload;
    }

    public function getPayload(): object
    {
        return $this->payload;
    }
}
