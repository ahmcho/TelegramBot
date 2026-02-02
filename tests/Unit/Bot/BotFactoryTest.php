<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Bot;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\BotFactory;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Bot Factory Tests
 *
 * Tests factory creates correct bot type and configuration passing.
 */
final class BotFactoryTest extends TestCase
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
    }

    public function test_create_returns_bot_instance(): void
    {
        $bot = BotFactory::create('test_token');

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_create_with_token_parameter(): void
    {
        $bot = BotFactory::create('custom_token');

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_create_with_null_token_uses_env(): void
    {
        $bot = BotFactory::create();

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_createWithConfig_returns_bot_instance(): void
    {
        $config = new BotConfig('factory_token');
        $bot = BotFactory::createWithConfig($config);

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_createWithConfig_passes_config(): void
    {
        $config = new BotConfig(
            token: 'factory_token',
            timeout: 60,
            verifySsl: true
        );

        $bot = BotFactory::createWithConfig($config);

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_createWithDatabase_returns_bot_instance(): void
    {
        $bot = BotFactory::createWithDatabase('test_token');

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_createWithDatabase_with_repository(): void
    {
        $repository = $this->createMock(\AhmCho\Telegram\Database\UserRepositoryInterface::class);
        $bot = BotFactory::createWithDatabase('test_token', $repository);

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_createWithHttpClient_returns_bot_instance(): void
    {
        $mockClient = new MockHttpClient();
        $bot = BotFactory::createWithHttpClient('test_token', $mockClient);

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_createWithHttpClient_passes_client(): void
    {
        $config = new BotConfig('test_token');
        $mockClient = new MockHttpClient();

        $bot = BotFactory::createWithHttpClient('test_token', $mockClient);

        // Verify the bot was created successfully
        $this->assertInstanceOf(TelegramBot::class, $bot);

        // Verify the mock client is being used by making a call
        $mockClient->setResponse(['id' => 123, 'first_name' => 'TestBot', 'is_bot' => true]);
        $result = $bot->getMe();

        $this->assertSame(123, $result['id']);
        $this->assertSame(1, $mockClient->getRequestCount());
    }

    public function test_createWithHttpClient_with_null_token_uses_env(): void
    {
        $mockClient = new MockHttpClient();
        $bot = BotFactory::createWithHttpClient(null, $mockClient);

        $this->assertInstanceOf(TelegramBot::class, $bot);
    }

    public function test_multiple_factory_calls_create_independent_bots(): void
    {
        $bot1 = BotFactory::create('token1');
        $bot2 = BotFactory::create('token2');

        $this->assertNotSame($bot1, $bot2);
    }
}
