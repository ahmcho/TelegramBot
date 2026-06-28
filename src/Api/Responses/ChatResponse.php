<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Responses;

/**
 * Chat Response
 *
 * Typed response for getChat and chat information
 */
class ChatResponse extends ApiResponse
{
    public function id(): int|string
    {
        return $this->get('result', [])['id'] ?? 0;
    }

    public function type(): string
    {
        return $this->get('result', [])['type'] ?? '';
    }

    public function title(): ?string
    {
        return $this->get('result', [])['title'] ?? null;
    }

    public function username(): ?string
    {
        return $this->get('result', [])['username'] ?? null;
    }

    public function firstName(): ?string
    {
        return $this->get('result', [])['first_name'] ?? null;
    }

    public function lastName(): ?string
    {
        return $this->get('result', [])['last_name'] ?? null;
    }

    public function description(): ?string
    {
        return $this->get('result', [])['description'] ?? null;
    }

    public function memberCount(): ?int
    {
        return $this->get('result', [])['member_count'] ?? null;
    }

    /**
     * Check if this is a private chat
     */
    public function isPrivate(): bool
    {
        return $this->type() === 'private';
    }

    /**
     * Check if this is a group
     */
    public function isGroup(): bool
    {
        return $this->type() === 'group';
    }

    /**
     * Check if this is a supergroup
     */
    public function isSupergroup(): bool
    {
        return $this->type() === 'supergroup';
    }

    /**
     * Check if this is a channel
     */
    public function isChannel(): bool
    {
        return $this->type() === 'channel';
    }
}
