<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Attributes;

#[\Attribute]
class RateLimit
{
    /**
     * @param int $requestsPerMinute Maximum requests per minute
     */
    public function __construct(
        public readonly int $requestsPerMinute = 30
    ) {}
}
