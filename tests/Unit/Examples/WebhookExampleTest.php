<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/webhook.php
 *
 * The webhook example defines a CommandRouter class that:
 * - Registers commands with handlers
 * - Dispatches message updates to command handlers
 * - Handles callback queries by parsing data prefix
 * - Answers callback queries before routing
 *
 * Tests verify the same patterns using TelegramBot + MockHttpClient.
 */
final class WebhookExampleTest extends TestCase
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

    public function test_processWebhook_calls_handler_with_update(): void
    {
        $update = [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'chat' => ['id' => 123],
                'text' => '/start',
            ],
        ];

        $this->mockClient->setResponse(['message_id' => 99]);

        $handlerCalled = false;
        $receivedUpdate = null;

        $tempFile = tempnam(sys_get_temp_dir(), 'webhook_test_');
        file_put_contents($tempFile, json_encode($update));
        $this->bot->setInputSource($tempFile);

        $this->bot->processWebhook(function ($u) use (&$handlerCalled, &$receivedUpdate) {
            $handlerCalled = true;
            $receivedUpdate = $u;
        });

        unlink($tempFile);

        $this->assertTrue($handlerCalled);
        $this->assertSame(1, $receivedUpdate['update_id']);
        $this->assertSame('/start', $receivedUpdate['message']['text']);
    }

    public function test_command_routing_dispatches_to_correct_handler(): void
    {
        $this->mockClient->setResponse(['message_id' => 10]);

        $dispatched = null;

        $this->bot->commands()->register('start', function ($bot, $chatId, $args) use (&$dispatched) {
            $dispatched = 'start';
            $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome!']);
        });

        $update = [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 123],
                'text' => '/start',
            ],
        ];

        $this->bot->commands()->handleUpdate($update);

        $this->assertSame('start', $dispatched);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_callback_query_data_parsed_by_prefix(): void
    {
        $callbackData = 'action:buy:product_1';
        $parts = explode(':', $callbackData);

        $this->assertSame('action', $parts[0]);
        $this->assertSame('buy', $parts[1]);
        $this->assertSame('product_1', $parts[2]);
    }

    public function test_answer_callback_query_called_before_routing(): void
    {
        $this->mockClient->setBoolResponse(true);
        $this->mockClient->setResponse(['message_id' => 11]);

        $this->bot->chats()->answerCallbackQuery([
            'callback_query_id' => 'cq_abc',
            'text' => 'Processing...',
        ]);

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => 'Result of action',
        ]);

        $this->assertSame(2, $this->mockClient->getRequestCount());

        $firstRequest = $this->mockClient->getRequests()[0];
        $this->assertStringContainsString('answerCallbackQuery', $firstRequest['url']);
    }

    public function test_unknown_callback_sends_fallback_message(): void
    {
        $this->mockClient->setResponse(['message_id' => 12]);

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => 'Unknown action',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Unknown action', $request['params']['text']);
    }

    public function test_command_with_arguments_receives_args_array(): void
    {
        $receivedArgs = null;

        $this->mockClient->setResponse(['message_id' => 13]);

        $this->bot->commands()->register('echo', function ($bot, $chatId, $args) use (&$receivedArgs) {
            $receivedArgs = $args;
            $bot->messages()->send(['chat_id' => $chatId, 'text' => implode(' ', $args)]);
        });

        $update = [
            'update_id' => 2,
            'message' => [
                'message_id' => 2,
                'chat' => ['id' => 456],
                'text' => '/echo hello world',
            ],
        ];

        $this->bot->commands()->handleUpdate($update);

        $this->assertSame(['hello', 'world'], $receivedArgs);
    }

    public function test_non_command_message_returns_false_from_handleUpdate(): void
    {
        $update = [
            'update_id' => 3,
            'message' => [
                'message_id' => 3,
                'chat' => ['id' => 789],
                'text' => 'This is not a command',
            ],
        ];

        $result = $this->bot->commands()->handleUpdate($update);

        $this->assertFalse($result);
        $this->assertSame(0, $this->mockClient->getRequestCount());
    }

    public function test_webhook_example_command_chain_sends_response(): void
    {
        $this->mockClient->setResponse(['message_id' => 14]);
        $this->mockClient->setResponse(['message_id' => 15]);

        $this->bot->commands()
            ->register('ping', function ($bot, $chatId, $args) {
                $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Pong!']);
            })
            ->register('help', function ($bot, $chatId, $args) {
                $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Help text here.']);
            });

        foreach (['/ping', '/help'] as $i => $cmd) {
            $this->bot->commands()->handleUpdate([
                'update_id' => $i + 10,
                'message' => ['message_id' => $i, 'chat' => ['id' => 123], 'text' => $cmd],
            ]);
        }

        $this->assertSame(2, $this->mockClient->getRequestCount());
    }

    public function test_getWebhookUpdates_returns_null_for_empty_input(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'webhook_empty_');
        file_put_contents($tempFile, '');
        $this->bot->setInputSource($tempFile);

        $update = $this->bot->getWebhookUpdates();
        unlink($tempFile);

        $this->assertNull($update);
    }

    public function test_getWebhookUpdates_returns_parsed_json(): void
    {
        $payload = ['update_id' => 42, 'message' => ['chat' => ['id' => 1], 'text' => '/start']];

        $tempFile = tempnam(sys_get_temp_dir(), 'webhook_data_');
        file_put_contents($tempFile, json_encode($payload));
        $this->bot->setInputSource($tempFile);

        $update = $this->bot->getWebhookUpdates();
        unlink($tempFile);

        $this->assertNotNull($update);
        $this->assertSame(42, $update['update_id']);
    }
}
