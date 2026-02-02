<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Database\UserEntity;

/**
 * User Entity Tests
 *
 * Tests fromTelegramUpdate creation, missing update data handling,
 * property access, and readonly immutability.
 */
final class UserEntityTest extends TestCase
{
    public function test_fromTelegramUpdate_with_message(): void
    {
        $update = [
            'update_id' => 1,
            'message' => [
                'message_id' => 100,
                'from' => [
                    'id' => 123456789,
                    'first_name' => 'John',
                    'last_name' => 'Doe',
                    'username' => 'johndoe',
                    'language_code' => 'en',
                    'is_bot' => false,
                    'is_premium' => true
                ],
                'chat' => [
                    'id' => 123456789,
                    'type' => 'private'
                ]
            ]
        ];

        $entity = UserEntity::fromTelegramUpdate($update);

        $this->assertNotNull($entity);
        $this->assertSame(123456789, $entity->telegramId);
        $this->assertSame(123456789, $entity->chatId);
        $this->assertSame('John', $entity->firstName);
        $this->assertSame('Doe', $entity->lastName);
        $this->assertSame('johndoe', $entity->username);
        $this->assertSame('en', $entity->languageCode);
        $this->assertFalse($entity->isBot);
        $this->assertTrue($entity->isPremium);
    }

    public function test_fromTelegramUpdate_with_callback_query(): void
    {
        $update = [
            'update_id' => 1,
            'callback_query' => [
                'id' => 'callback_123',
                'from' => [
                    'id' => 987654321,
                    'first_name' => 'Jane',
                    'is_bot' => false
                ],
                'message' => [
                    'message_id' => 100,
                    'chat' => ['id' => 987654321, 'type' => 'private']
                ]
            ]
        ];

        $entity = UserEntity::fromTelegramUpdate($update);

        $this->assertNotNull($entity);
        $this->assertSame(987654321, $entity->telegramId);
        $this->assertSame('Jane', $entity->firstName);
    }

    public function test_fromTelegramUpdate_with_inline_query(): void
    {
        $update = [
            'update_id' => 1,
            'inline_query' => [
                'id' => 'inline_123',
                'from' => [
                    'id' => 111111111,
                    'first_name' => 'Bot',
                    'is_bot' => true
                ],
                'query' => 'test'
            ]
        ];

        $entity = UserEntity::fromTelegramUpdate($update);

        $this->assertNotNull($entity);
        $this->assertSame(111111111, $entity->telegramId);
        $this->assertSame(111111111, $entity->chatId); // Falls back to user id
        $this->assertTrue($entity->isBot);
    }

    public function test_fromTelegramUpdate_returns_null_for_invalid_update(): void
    {
        $update = ['update_id' => 1];

        $entity = UserEntity::fromTelegramUpdate($update);

        $this->assertNull($entity);
    }

    public function test_fromTelegramUpdate_handles_missing_optional_fields(): void
    {
        $update = [
            'update_id' => 1,
            'message' => [
                'from' => [
                    'id' => 123456789,
                    'first_name' => 'Test'
                ],
                'chat' => ['id' => 123456789, 'type' => 'private']
            ]
        ];

        $entity = UserEntity::fromTelegramUpdate($update);

        $this->assertNotNull($entity);
        $this->assertNull($entity->lastName);
        $this->assertNull($entity->username);
        $this->assertNull($entity->languageCode);
        $this->assertFalse($entity->isPremium);
    }

    public function test_fromDatabaseRow_creates_entity(): void
    {
        $row = [
            'id' => 1,
            'telegram_id' => 123456789,
            'chat_id' => 123456789,
            'first_name' => 'John',
            'last_name' => 'Doe',
            'username' => 'johndoe',
            'language_code' => 'en',
            'is_bot' => 0,
            'is_premium' => 1,
            'created_at' => '2024-01-01 00:00:00',
            'updated_at' => '2024-01-01 00:00:00',
            'last_active' => '2024-01-01 00:00:00'
        ];

        $entity = UserEntity::fromDatabaseRow($row);

        $this->assertSame(1, $entity->id);
        $this->assertSame(123456789, $entity->telegramId);
        $this->assertSame('John', $entity->firstName);
        $this->assertFalse($entity->isBot);
        $this->assertTrue($entity->isPremium);
    }

    public function test_entity_is_readonly(): void
    {
        $update = [
            'message' => [
                'from' => ['id' => 123, 'first_name' => 'Test'],
                'chat' => ['id' => 123, 'type' => 'private']
            ]
        ];

        $entity = UserEntity::fromTelegramUpdate($update);

        $reflection = new \ReflectionClass($entity);
        $this->assertTrue($reflection->isReadOnly());
    }

    public function test_withUpdatedLastActive_creates_new_instance(): void
    {
        $update = [
            'message' => [
                'from' => ['id' => 123, 'first_name' => 'Test'],
                'chat' => ['id' => 123, 'type' => 'private']
            ]
        ];

        $entity = UserEntity::fromTelegramUpdate($update);
        sleep(1); // Ensure time difference
        $updated = $entity->withUpdatedLastActive();

        $this->assertNotSame($entity, $updated);
        $this->assertGreaterThan($entity->lastActive->getTimestamp(), $updated->lastActive->getTimestamp());
    }
}
