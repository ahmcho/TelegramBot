<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Database;

use DateTimeImmutable;

/**
 * User Entity Value Object
 *
 * Immutable representation of a Telegram user
 */
readonly class UserEntity
{
    public function __construct(
        public int $id,
        public int $telegramId,
        public int $chatId,
        public ?string $firstName,
        public ?string $lastName,
        public ?string $username,
        public ?string $languageCode,
        public bool $isBot,
        public bool $isPremium,
        public DateTimeImmutable $createdAt,
        public DateTimeImmutable $updatedAt,
        public DateTimeImmutable $lastActive
    ) {
    }

    /**
     * @param array<mixed> $data
     */
    public static function fromDatabaseRow(array $data): self
    {
        return new self(
            id: (int) $data['id'],
            telegramId: (int) $data['telegram_id'],
            chatId: (int) $data['chat_id'],
            firstName: $data['first_name'] ?: null,
            lastName: $data['last_name'] ?: null,
            username: $data['username'] ?: null,
            languageCode: $data['language_code'] ?: null,
            isBot: (bool) $data['is_bot'],
            isPremium: (bool) $data['is_premium'],
            createdAt: new DateTimeImmutable($data['created_at']),
            updatedAt: new DateTimeImmutable($data['updated_at']),
            lastActive: new DateTimeImmutable($data['last_active'])
        );
    }

    /**
     * @param array<mixed> $update
     */
    public static function fromTelegramUpdate(array $update): ?self
    {
        $userData = self::extractUserData($update);

        if ($userData === null) {
            return null;
        }

        $now = new DateTimeImmutable();

        return new self(
            id: 0, // Not yet saved
            telegramId: $userData['telegram_id'],
            chatId: $userData['chat_id'],
            firstName: $userData['first_name'],
            lastName: $userData['last_name'],
            username: $userData['username'],
            languageCode: $userData['language_code'],
            isBot: $userData['is_bot'],
            isPremium: $userData['is_premium'],
            createdAt: $now,
            updatedAt: $now,
            lastActive: $now
        );
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function extractUserData(array $update): ?array
    {
        $user = null;
        $chatId = null;

        // Match expression for cleaner update type handling
        $user = match (true) {
            isset($update['message']['from']) => $update['message']['from'],
            isset($update['callback_query']['from']) => $update['callback_query']['from'],
            isset($update['inline_query']['from']) => $update['inline_query']['from'],
            isset($update['chosen_inline_result']['from']) => $update['chosen_inline_result']['from'],
            isset($update['shipping_query']['from']) => $update['shipping_query']['from'],
            isset($update['pre_checkout_query']['from']) => $update['pre_checkout_query']['from'],
            isset($update['poll_answer']['user']) => $update['poll_answer']['user'],
            isset($update['my_chat_member']['from']) => $update['my_chat_member']['from'],
            isset($update['chat_member']['from']) => $update['chat_member']['from'],
            isset($update['chat_join_request']['from']) => $update['chat_join_request']['from'],
            default => null
        };

        if ($user === null) {
            return null;
        }

        $chatId = match (true) {
            isset($update['message']['chat']['id']) => $update['message']['chat']['id'],
            isset($update['callback_query']['message']['chat']['id']) => $update['callback_query']['message']['chat']['id'],
            default => $user['id']
        };

        return [
            'telegram_id' => $user['id'],
            'chat_id' => $chatId,
            'first_name' => $user['first_name'] ?? null,
            'last_name' => $user['last_name'] ?? null,
            'username' => $user['username'] ?? null,
            'language_code' => $user['language_code'] ?? null,
            'is_bot' => $user['is_bot'] ?? false,
            'is_premium' => $user['is_premium'] ?? false,
        ];
    }

    public function withUpdatedLastActive(): self
    {
        return new self(
            id: $this->id,
            telegramId: $this->telegramId,
            chatId: $this->chatId,
            firstName: $this->firstName,
            lastName: $this->lastName,
            username: $this->username,
            languageCode: $this->languageCode,
            isBot: $this->isBot,
            isPremium: $this->isPremium,
            createdAt: $this->createdAt,
            updatedAt: new DateTimeImmutable(),
            lastActive: new DateTimeImmutable()
        );
    }
}
