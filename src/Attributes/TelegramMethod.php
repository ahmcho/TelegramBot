<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Attributes;

#[\Attribute]
class TelegramMethod
{
    /**
     * @param string $method The Telegram API method name
     * @param array<string> $requiredParams Array of required parameter names
     * @param array<string> $optionalParams Array of optional parameter names
     */
    public function __construct(
        public readonly string $method,
        public readonly array $requiredParams = [],
        public readonly array $optionalParams = []
    ) {}
}
