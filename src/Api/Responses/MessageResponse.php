<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Responses;

/**
 * Message Response
 *
 * Typed response for sendMessage and similar methods
 */
class MessageResponse extends ApiResponse
{
    public function id(): int
    {
        return (int) $this->get('result', [])['message_id'] ?? 0;
    }

    public function chatId(): int|string
    {
        return $this->get('result', [])['chat']['id'] ?? 0;
    }

    public function date(): int
    {
        return (int) ($this->get('result', [])['date'] ?? 0);
    }

    public function text(): ?string
    {
        return $this->get('result', [])['text'] ?? null;
    }
}
