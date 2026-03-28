<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Bot;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Client\HttpClientFactory;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Tests\Helpers\TestDataFactory;
use AhmCho\Telegram\Tests\Helpers\WebhookStreamWrapper;

/**
 * Telegram Bot Tests
 *
 * Tests construction, service accessors, convenience methods,
 * webhook handling, and database integration.
 */
final class TelegramBotTest extends TestCase
{
    private string|false|null $envToken = null;

    protected function setUp(): void
    {
        parent::setUp();

        // Save original env value
        $this->envToken = getenv('TELEGRAM_BOT_TOKEN');

        // Set test token
        putenv('TELEGRAM_BOT_TOKEN=test_token_123456');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // Restore original env value
        if ($this->envToken === false) {
            putenv('TELEGRAM_BOT_TOKEN');
        } else {
            putenv('TELEGRAM_BOT_TOKEN=' . $this->envToken);
        }

        // Clean up webhook stream wrapper
        WebhookStreamWrapper::unregister();
    }

    public function test_construction_with_token(): void
    {
        $bot = new TelegramBot('custom_token');

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_construction_with_config(): void
    {
        $config = new BotConfig('test_token');
        $bot = new TelegramBot(null, $config);

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_construction_with_http_client(): void
    {
        $config = new BotConfig('test_token');
        $mockClient = new MockHttpClient();
        $bot = new TelegramBot(null, $config, $mockClient);

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_messages_accessor_returns_service(): void
    {
        $bot = new TelegramBot('test_token');

        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\MessageService::class, $bot->messages());
    }

    public function test_media_accessor_returns_service(): void
    {
        $bot = new TelegramBot('test_token');

        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\MediaService::class, $bot->media());
    }

    public function test_chats_accessor_returns_service(): void
    {
        $bot = new TelegramBot('test_token');

        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\ChatService::class, $bot->chats());
    }

    public function test_webhooks_accessor_returns_service(): void
    {
        $bot = new TelegramBot('test_token');

        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\WebhookService::class, $bot->webhooks());
    }

    public function test_formatter_accessor_returns_formatter(): void
    {
        $bot = new TelegramBot('test_token');

        $this->assertInstanceOf(\AhmCho\Telegram\Formatting\MarkdownV2Formatter::class, $bot->formatter());
    }

    public function test_api_accessor_returns_service(): void
    {
        $bot = new TelegramBot('test_token');

        $this->assertInstanceOf(\AhmCho\Telegram\Api\ApiService::class, $bot->api());
    }

    public function test_sendMessage_convenience_method(): void
    {
        $config = new BotConfig('test_token');
        $mockClient = new MockHttpClient();
        $mockClient->setResponse(['message_id' => 123, 'chat' => ['id' => 123]]);

        $bot = new TelegramBot(null, $config, $mockClient);
        $result = $bot->sendMessage(['chat_id' => 123, 'text' => 'test']);

        $this->assertSame(123, $result['message_id']);
    }

    public function test_sendPhoto_convenience_method(): void
    {
        $config = new BotConfig('test_token');
        $mockClient = new MockHttpClient();
        $mockClient->setResponse(['message_id' => 456, 'chat' => ['id' => 123]]);

        $bot = new TelegramBot(null, $config, $mockClient);
        $result = $bot->sendPhoto(['chat_id' => 123, 'photo' => 'https://example.com/photo.jpg']);

        $this->assertSame(456, $result['message_id']);
    }

    public function test_getMe_convenience_method(): void
    {
        $config = new BotConfig('test_token');
        $mockClient = new MockHttpClient();
        $mockClient->setResponse(['id' => 123, 'first_name' => 'TestBot', 'is_bot' => true]);

        $bot = new TelegramBot(null, $config, $mockClient);
        $result = $bot->getMe();

        $this->assertSame(123, $result['id']);
        $this->assertSame('TestBot', $result['first_name']);
    }

    public function test_getUpdates_convenience_method(): void
    {
        $config = new BotConfig('test_token');
        $mockClient = new MockHttpClient();
        $mockClient->setResponse([]);

        $bot = new TelegramBot(null, $config, $mockClient);
        $result = $bot->getUpdates();

        $this->assertSame([], $result);
    }

    public function test_getWebhookUpdates_with_valid_json(): void
    {
        WebhookStreamWrapper::register();

        $testUpdate = [
            'update_id' => 1,
            'message' => [
                'message_id' => 123,
                'chat' => ['id' => 456789, 'type' => 'private'],
                'from' => ['id' => 456789, 'first_name' => 'Test'],
                'text' => 'Hello'
            ]
        ];

        WebhookStreamWrapper::setData(json_encode($testUpdate));

        $bot = new TelegramBot('test_token');
        $bot->setInputSource('webhook-test://input');
        $result = $bot->getWebhookUpdates();

        $this->assertSame($testUpdate, $result);
        $this->assertSame(1, $result['update_id']);
        $this->assertSame('Hello', $result['message']['text']);

        WebhookStreamWrapper::clear();
    }

    public function test_processWebhook_calls_handler_with_update(): void
    {
        WebhookStreamWrapper::register();

        $testUpdate = [
            'update_id' => 1,
            'message' => [
                'message_id' => 123,
                'chat' => ['id' => 456789, 'type' => 'private'],
                'from' => ['id' => 456789, 'first_name' => 'Test'],
                'text' => 'Test message'
            ]
        ];

        WebhookStreamWrapper::setData(json_encode($testUpdate));

        $bot = new TelegramBot('test_token');
        $bot->setInputSource('webhook-test://input');

        $handlerCalled = false;
        $receivedUpdate = null;

        $handler = function ($update) use (&$handlerCalled, &$receivedUpdate) {
            $handlerCalled = true;
            $receivedUpdate = $update;
        };

        $bot->processWebhook($handler);

        $this->assertTrue($handlerCalled);
        $this->assertSame($testUpdate, $receivedUpdate);
        $this->assertSame('Test message', $receivedUpdate['message']['text']);

        WebhookStreamWrapper::clear();
    }

    public function test_getLogger_returns_logger_instance(): void
    {
        $config = new BotConfig(
            token: 'test_token',
            loggingEnabled: true
        );

        $mockClient = new MockHttpClient();
        $bot = new TelegramBot(null, $config, $mockClient);

        $logger = $bot->getLogger();
        $this->assertIsObject($logger);
    }

    public function test_saveUserFromUpdate_saves_user(): void
    {
        $config = new BotConfig('test_token');
        $mockClient = new MockHttpClient();
        $bot = new TelegramBot(null, $config, $mockClient);

        // Use in-memory database
        $repository = new SqliteUserRepository(':memory:');

        $bot->setUserRepository($repository);

        $update = TestDataFactory::createMessageUpdate(
            update_id: 1,
            message_id: 100,
            chat_id: 123456789,
            user_id: 123456789,
            text: 'Test message'
        );

        $result = $bot->saveUserFromUpdate($update);

        $this->assertTrue($result);

        $savedUser = $repository->findByTelegramId(123456789);
        $this->assertNotNull($savedUser);
        $this->assertSame(123456789, $savedUser->telegramId);
    }

    public function test_saveUserFromUpdate_throws_when_repository_not_set(): void
    {
        $bot = new TelegramBot('test_token');
        $update = TestDataFactory::createMessageUpdate();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User repository not configured');

        $bot->saveUserFromUpdate($update);
    }

    public function test_broadcastToDatabase_sends_to_all_users(): void
    {
        $config = new BotConfig('test_token', throwExceptions: false);
        $mockClient = new MockHttpClient();

        // Mock successful bulk response - use correct format for setResponses
        $mockClient->setResponses([
            ['response' => ['message_id' => 1, 'chat' => ['id' => 100]], 'exception' => null, 'http_code' => 200],
            ['response' => ['message_id' => 2, 'chat' => ['id' => 200]], 'exception' => null, 'http_code' => 200],
        ]);

        $bot = new TelegramBot(null, $config, $mockClient);

        // Use in-memory database
        $repository = new SqliteUserRepository(':memory:');

        // Add test users
        $repository->save(new UserEntity(
            id: 0,
            telegramId: 1,
            chatId: 100,
            firstName: 'User1',
            lastName: null,
            username: null,
            languageCode: null,
            isBot: false,
            isPremium: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $repository->save(new UserEntity(
            id: 0,
            telegramId: 2,
            chatId: 200,
            firstName: 'User2',
            lastName: null,
            username: null,
            languageCode: null,
            isBot: false,
            isPremium: false,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $bot->setUserRepository($repository);

        $result = $bot->broadcastToDatabase('Broadcast message');

        $this->assertTrue($result->isSuccess());
        $this->assertSame(2, $result->total);
    }

    public function test_broadcastToDatabase_throws_when_repository_not_set(): void
    {
        $bot = new TelegramBot('test_token');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('User repository not configured');

        $bot->broadcastToDatabase('Test message');
    }

    public function test_broadcastToDatabase_with_filters(): void
    {
        $config = new BotConfig('test_token', throwExceptions: false);
        $mockClient = new MockHttpClient();
        $mockClient->setResponse(['message_id' => 1, 'chat' => ['id' => 100]]);

        $bot = new TelegramBot(null, $config, $mockClient);

        $repository = new SqliteUserRepository(':memory:');
        $repository->save(new UserEntity(
            id: 0,
            telegramId: 1,
            chatId: 100,
            firstName: 'Premium',
            lastName: null,
            username: 'premium_user',
            languageCode: null,
            isBot: false,
            isPremium: true,
            createdAt: new \DateTimeImmutable(),
            updatedAt: new \DateTimeImmutable(),
            lastActive: new \DateTimeImmutable()
        ));

        $bot->setUserRepository($repository);

        $filters = \AhmCho\Telegram\Database\UserFilters::create()->withIsPremium(true);
        $result = $bot->broadcastToDatabase('Premium only', [], $filters);

        $this->assertTrue($result->isSuccess());
    }
}
