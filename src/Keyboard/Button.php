<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Keyboard;

/**
 * Button Value Object
 *
 * Immutable button representation for keyboards
 */
readonly class Button
{
    /**
     * @param array<string, mixed> $metadata
     */
    private function __construct(
        public readonly string $text,
        public readonly ?string $url,
        public readonly ?string $callbackData,
        public readonly ?string $switchInlineQuery,
        public readonly ?string $switchInlineQueryCurrentChat,
        public readonly array $metadata
    ) {
    }

    public static function url(string $text, string $url): self
    {
        return new self(
            text: $text,
            url: $url,
            callbackData: null,
            switchInlineQuery: null,
            switchInlineQueryCurrentChat: null,
            metadata: []
        );
    }

    public static function callback(string $text, string $data): self
    {
        return new self(
            text: $text,
            url: null,
            callbackData: $data,
            switchInlineQuery: null,
            switchInlineQueryCurrentChat: null,
            metadata: []
        );
    }

    public static function switchInline(string $text, string $query = ''): self
    {
        return new self(
            text: $text,
            url: null,
            callbackData: null,
            switchInlineQuery: $query,
            switchInlineQueryCurrentChat: null,
            metadata: []
        );
    }

    public static function switchInlineCurrent(string $text, string $query = ''): self
    {
        return new self(
            text: $text,
            url: null,
            callbackData: null,
            switchInlineQuery: null,
            switchInlineQueryCurrentChat: $query,
            metadata: []
        );
    }

    public static function text(string $text): self
    {
        return new self(
            text: $text,
            url: null,
            callbackData: null,
            switchInlineQuery: null,
            switchInlineQueryCurrentChat: null,
            metadata: []
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $button = ['text' => $this->text];

        if ($this->url !== null) {
            $button['url'] = $this->url;
        }

        if ($this->callbackData !== null) {
            $button['callback_data'] = $this->callbackData;
        }

        if ($this->switchInlineQuery !== null) {
            $button['switch_inline_query'] = $this->switchInlineQuery;
        }

        if ($this->switchInlineQueryCurrentChat !== null) {
            $button['switch_inline_query_current_chat'] = $this->switchInlineQueryCurrentChat;
        }

        return [...$button, ...$this->metadata];
    }
}
