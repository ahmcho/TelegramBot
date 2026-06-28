<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/setup-webhook.php
 *
 * Verifies the webhook setup patterns:
 * - webhooks()->set() accepts URL and optional secret_token
 * - webhooks()->delete() removes the webhook
 * - webhooks()->getInfo() returns webhook information
 * - Multiple options like drop_pending_updates, allowed_updates
 */
final class SetupWebhookExampleTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
    }

    public function test_set_webhook_with_url(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->webhooks()->set([
            'url' => 'https://example.com/webhook.php',
        ]);

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $this->assertSame('https://example.com/webhook.php', $request['params']['url']);
    }

    public function test_set_webhook_with_url_and_secret_token(): void
    {
        $this->mockClient->setBoolResponse(true);

        $this->bot->webhooks()->set([
            'url' => 'https://example.com/webhook.php',
            'secret_token' => 'my_secret_token_abc123',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('my_secret_token_abc123', $request['params']['secret_token']);
    }

    public function test_set_webhook_with_drop_pending_updates(): void
    {
        $this->mockClient->setBoolResponse(true);

        $this->bot->webhooks()->set([
            'url' => 'https://example.com/webhook.php',
            'drop_pending_updates' => true,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertTrue($request['params']['drop_pending_updates']);
    }

    public function test_set_webhook_with_allowed_updates(): void
    {
        $this->mockClient->setBoolResponse(true);

        $this->bot->webhooks()->set([
            'url' => 'https://example.com/webhook.php',
            'allowed_updates' => ['message', 'callback_query'],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame(['message', 'callback_query'], $request['params']['allowed_updates']);
    }

    public function test_set_webhook_with_max_connections(): void
    {
        $this->mockClient->setBoolResponse(true);

        $this->bot->webhooks()->set([
            'url' => 'https://example.com/webhook.php',
            'max_connections' => 100,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame(100, $request['params']['max_connections']);
    }

    public function test_delete_webhook(): void
    {
        $this->mockClient->setBoolResponse(true);

        $result = $this->bot->webhooks()->delete();

        $this->assertTrue($result);
        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('deleteWebhook', $request['url']);
    }

    public function test_delete_webhook_with_drop_pending(): void
    {
        $this->mockClient->setBoolResponse(true);

        $this->bot->webhooks()->delete(['drop_pending_updates' => true]);

        $request = $this->mockClient->getLastRequest();
        $this->assertTrue($request['params']['drop_pending_updates']);
    }

    public function test_get_webhook_info_returns_info(): void
    {
        $this->mockClient->setResponse([
            'url' => 'https://example.com/webhook.php',
            'has_custom_certificate' => false,
            'pending_update_count' => 0,
            'max_connections' => 40,
            'allowed_updates' => ['message', 'callback_query'],
        ]);

        $info = $this->bot->webhooks()->getInfo();

        $this->assertSame('https://example.com/webhook.php', $info['url']);
        $this->assertSame(40, $info['max_connections']);
        $this->assertSame(0, $info['pending_update_count']);
    }

    public function test_full_setup_workflow(): void
    {
        $this->mockClient->setBoolResponse(true);
        $this->mockClient->setResponse([
            'url' => 'https://example.com/webhook.php',
            'pending_update_count' => 0,
        ]);
        $this->mockClient->setBoolResponse(true);

        $this->bot->webhooks()->set([
            'url' => 'https://example.com/webhook.php',
            'drop_pending_updates' => true,
        ]);

        $info = $this->bot->webhooks()->getInfo();

        $this->bot->webhooks()->delete();

        $this->assertSame(3, $this->mockClient->getRequestCount());
        $this->assertSame('https://example.com/webhook.php', $info['url']);
    }
}
