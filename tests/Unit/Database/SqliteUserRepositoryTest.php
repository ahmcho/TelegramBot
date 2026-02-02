<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Database;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Database\SqliteUserRepository;
use AhmCho\Telegram\Database\UserEntity;
use AhmCho\Telegram\Database\UserFilters;
use AhmCho\Telegram\Exception\TelegramException;

/**
 * SQLite User Repository Tests
 *
 * Tests user saving, retrieval, filtering, updates,
 * database initialization using in-memory SQLite.
 */
final class SqliteUserRepositoryTest extends TestCase
{
    private SqliteUserRepository $repository;
    private string $dbPath;

    protected function setUp(): void
    {
        parent::setUp();

        if (!extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('pdo_sqlite extension is not available');
        }

        // Use in-memory database for tests
        $this->dbPath = ':memory:';
        $this->repository = new SqliteUserRepository($this->dbPath);
    }

    public function test_constructor_creates_tables(): void
    {
        $pdo = $this->repository->getPdo();
        $result = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");

        $this->assertNotFalse($result);
        $this->assertSame('users', $result->fetch()['name']);
    }

    public function test_save_inserts_new_user(): void
    {
        $user = new UserEntity(
            id: 0,
            telegramId: 123456789,
            chatId: 123456789,
            firstName: 'John',
            lastName: 'Doe',
            username: 'johndoe',
            languageCode: 'en',
            isBot: false,
            isPremium: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        );

        $result = $this->repository->save($user);

        $this->assertTrue($result);
    }

    public function test_save_updates_existing_user(): void
    {
        $user = new UserEntity(
            id: 0,
            telegramId: 123456789,
            chatId: 123456789,
            firstName: 'John',
            lastName: null,
            username: null,
            languageCode: null,
            isBot: false,
            isPremium: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        );

        $this->repository->save($user);

        $updatedUser = new UserEntity(
            id: 0,
            telegramId: 123456789,
            chatId: 123456789,
            firstName: 'John Updated',
            lastName: 'Doe',
            username: 'johndoe',
            languageCode: 'en',
            isBot: false,
            isPremium: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        );

        $result = $this->repository->save($updatedUser);

        $this->assertTrue($result);
    }

    public function test_findByTelegramId_returns_user(): void
    {
        $user = new UserEntity(
            id: 0,
            telegramId: 123456789,
            chatId: 123456789,
            firstName: 'Test',
            lastName: null,
            username: null,
            languageCode: null,
            isBot: false,
            isPremium: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        );

        $this->repository->save($user);

        $found = $this->repository->findByTelegramId(123456789);

        $this->assertNotNull($found);
        $this->assertSame(123456789, $found->telegramId);
        $this->assertSame('Test', $found->firstName);
    }

    public function test_findByTelegramId_returns_null_for_nonexistent(): void
    {
        $found = $this->repository->findByTelegramId(999999999);

        $this->assertNull($found);
    }

    public function test_findByUsername_returns_user(): void
    {
        $user = new UserEntity(
            id: 0,
            telegramId: 123456789,
            chatId: 123456789,
            firstName: 'Test',
            lastName: null,
            username: 'testuser',
            languageCode: null,
            isBot: false,
            isPremium: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        );

        $this->repository->save($user);

        $found = $this->repository->findByUsername('testuser');

        $this->assertNotNull($found);
        $this->assertSame('testuser', $found->username);
    }

    public function test_findByUsername_handles_at_symbol(): void
    {
        $user = new UserEntity(
            id: 0,
            telegramId: 123456789,
            chatId: 123456789,
            firstName: 'Test',
            lastName: null,
            username: 'testuser',
            languageCode: null,
            isBot: false,
            isPremium: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        );

        $this->repository->save($user);

        $found = $this->repository->findByUsername('@testuser');

        $this->assertNotNull($found);
    }

    public function test_getAllChatIds_returns_all_ids(): void
    {
        $this->repository->save(new UserEntity(
            id: 0, telegramId: 1, chatId: 100, firstName: 'A', lastName: null,
            username: null, languageCode: null, isBot: false, isPremium: false,
            createdAt: new \DateTimeImmutable(), updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $this->repository->save(new UserEntity(
            id: 0, telegramId: 2, chatId: 200, firstName: 'B', lastName: null,
            username: null, languageCode: null, isBot: false, isPremium: false,
            createdAt: new \DateTimeImmutable(), updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $chatIds = $this->repository->getAllChatIds();

        $this->assertCount(2, $chatIds);
        $this->assertContains(100, $chatIds);
        $this->assertContains(200, $chatIds);
    }

    public function test_getAllChatIds_with_filters(): void
    {
        $filters = UserFilters::create()->withLimit(1);

        $this->repository->save(new UserEntity(
            id: 0, telegramId: 1, chatId: 100, firstName: 'A', lastName: null,
            username: 'user_a', languageCode: null, isBot: false, isPremium: true,
            createdAt: new \DateTimeImmutable(), updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $chatIds = $this->repository->getAllChatIds($filters);

        $this->assertCount(1, $chatIds);
    }

    public function test_findAll_returns_users(): void
    {
        $this->repository->save(new UserEntity(
            id: 0, telegramId: 1, chatId: 100, firstName: 'User1', lastName: null,
            username: null, languageCode: null, isBot: false, isPremium: false,
            createdAt: new \DateTimeImmutable(), updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $users = $this->repository->findAll();

        $this->assertCount(1, $users);
        $this->assertSame('User1', $users[0]->firstName);
    }

    public function test_findAll_with_limit_and_offset(): void
    {
        for ($i = 1; $i <= 5; $i++) {
            $this->repository->save(new UserEntity(
                id: 0, telegramId: $i, chatId: $i * 100, firstName: "User$i", lastName: null,
                username: null, languageCode: null, isBot: false, isPremium: false,
                createdAt: new \DateTimeImmutable(), updatedAt: new \DateTimeImmutable(),
                lastActive: new \DateTimeImmutable()
            ));
        }

        $users = $this->repository->findAll(null, 2, 2);

        $this->assertCount(2, $users);
    }

    public function test_updateLastActive_updates_timestamp(): void
    {
        $user = new UserEntity(
            id: 0, telegramId: 123456789, chatId: 123456789, firstName: 'Test',
            lastName: null, username: null, languageCode: null, isBot: false,
            isPremium: false, createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(), lastActive: new \DateTimeImmutable()
        );

        $this->repository->save($user);

        $result = $this->repository->updateLastActive(123456789);

        $this->assertTrue($result);
    }

    public function test_delete_removes_user(): void
    {
        $user = new UserEntity(
            id: 0, telegramId: 123456789, chatId: 123456789, firstName: 'Test',
            lastName: null, username: null, languageCode: null, isBot: false,
            isPremium: false, createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(), lastActive: new \DateTimeImmutable()
        );

        $this->repository->save($user);

        $result = $this->repository->delete(123456789);

        $this->assertTrue($result);

        $found = $this->repository->findByTelegramId(123456789);
        $this->assertNull($found);
    }

    public function test_getStats_returns_statistics(): void
    {
        $stats = $this->repository->getStats();

        $this->assertArrayHasKey('total', $stats);
        $this->assertArrayHasKey('active_30_days', $stats);
        $this->assertArrayHasKey('premium', $stats);
        $this->assertArrayHasKey('with_username', $stats);
        $this->assertArrayHasKey('bots', $stats);
        $this->assertArrayHasKey('new_today', $stats);

        $this->assertSame(0, $stats['total']);
    }

    public function test_getStats_with_users(): void
    {
        $this->repository->save(new UserEntity(
            id: 0, telegramId: 1, chatId: 100, firstName: 'User', lastName: null,
            username: 'username', languageCode: 'en', isBot: false, isPremium: true,
            createdAt: new \DateTimeImmutable(), updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $stats = $this->repository->getStats();

        $this->assertSame(1, $stats['total']);
        $this->assertSame(1, $stats['premium']);
        $this->assertSame(1, $stats['with_username']);
    }

    public function test_constructor_throws_without_pdo(): void
    {
        // This test documents the expected behavior when PDO is unavailable
        // We cannot disable PDO at runtime, but we can verify the exception message
        // The actual PDO check happens in the constructor via ensureExtensionsLoaded()

        if (extension_loaded('pdo') && extension_loaded('pdo_sqlite')) {
            $this->markTestSkipped('PDO extensions are available - cannot test unavailable scenario');
        }

        // If we reach here, PDO is actually unavailable
        $this->expectException(TelegramException::class);
        $this->expectExceptionMessageMatches('/PDO.*extension is not enabled/');

        new SqliteUserRepository(':memory:');
    }
}
