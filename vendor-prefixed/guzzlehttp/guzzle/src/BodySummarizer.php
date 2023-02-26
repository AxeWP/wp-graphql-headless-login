<?php
/**
 * @license MIT
 *
 * Modified by AxePress Development using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace WPGraphQL\Login\Vendor\GuzzleHttp;

use WPGraphQL\Login\Vendor\Psr\Http\Message\MessageInterface;

final class BodySummarizer implements BodySummarizerInterface
{
    /**
     * @var int|null
     */
    private $truncateAt;

    public function __construct(int $truncateAt = null)
    {
        $this->truncateAt = $truncateAt;
    }

    /**
     * Returns a summarized message body.
     */
    public function summarize(MessageInterface $message): ?string
    {
        return $this->truncateAt === null
            ? \WPGraphQL\Login\Vendor\GuzzleHttp\Psr7\Message::bodySummary($message)
            : \WPGraphQL\Login\Vendor\GuzzleHttp\Psr7\Message::bodySummary($message, $this->truncateAt);
    }
}
