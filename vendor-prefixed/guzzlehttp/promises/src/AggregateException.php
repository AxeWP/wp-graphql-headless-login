<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPGraphQL\Login\Vendor\GuzzleHttp\Promise;

/**
 * Exception thrown when too many errors occur in the some() or any() methods.
 */
class AggregateException extends RejectionException
{
    public function __construct($msg, array $reasons)
    {
        parent::__construct(
            $reasons,
            sprintf('%s; %d rejected promises', $msg, count($reasons))
        );
    }
}
