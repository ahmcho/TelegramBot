<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Api\Responses;

/**
 * User Response
 *
 * Typed response for getMe and user information
 */
class UserResponse extends ApiResponse
{
    public function id(): int
    {
        return (int) $this->get('result', [])['id'] ?? 0;
    }

    public function isBot(): bool
    {
        return $this->get('result', [])['is_bot'] ?? false;
    }

    public function firstName(): string
    {
        return $this->get('result', [])['first_name'] ?? '';
    }

    public function lastName(): ?string
    {
        return $this->get('result', [])['last_name'] ?? null;
    }

    public function username(): ?string
    {
        return $this->get('result', [])['username'] ?? null;
    }

    public function languageCode(): ?string
    {
        return $this->get('result', [])['language_code'] ?? null;
    }

    public function isPremium(): bool
    {
        return $this->get('result', [])['is_premium'] ?? false;
    }

    /**
     * Get full name (first + last)
     */
    public function fullName(): string
    {
        $name = $this->firstName();
        $lastName = $this->lastName();

        return $lastName ? "{$name} {$lastName}" : $name;
    }
}
