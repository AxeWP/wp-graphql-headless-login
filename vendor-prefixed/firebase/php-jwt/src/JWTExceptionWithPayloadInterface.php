<?php
/**
 * @license BSD-3-Clause
 *
 * Modified by AxePress Development using {@see https://github.com/BrianHenryIE/strauss}.
 */
namespace WPGraphQL\Login\Vendor\Firebase\JWT;

interface JWTExceptionWithPayloadInterface
{
    /**
     * Get the payload that caused this exception.
     *
     * @return object
     */
    public function getPayload(): object;

    /**
     * Get the payload that caused this exception.
     *
     * @param object $payload
     * @return void
     */
    public function setPayload(object $payload): void;
}
