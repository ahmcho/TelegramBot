<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/commands-demo.php
 *
 * Verifies the command handler system demonstrated in the example:
 * - Commands are registered with descriptions
 * - getRegisteredCommands() returns all command names
 * - handleUpdate() dispatches to correct command
 * - handleUpdate() returns false for non-command messages
 * - sendHelp() sends formatted help with all commands
 * - Middleware runs before command execution
 * - Default handler is called for unknown commands
 * - Command with args (echo, time) parses arguments
 */
final class CommandsDemoExampleTest extends TestCase
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

    public function test_register_multiple_commands(): void
    {
        $this->bot->commands()
            ->register('start', fn($b, $id, $args) => null, 'Start the bot and see this message')
            ->register('help', fn($b, $id, $args) => null, 'Show this help message')
            ->register('ping', fn($b, $id, $args) => null, 'Check bot response time')
            ->register('echo', fn($b, $id, $args) => null, 'Echo back your message')
            ->register('info', fn($b, $id, $args) => null, 'Get bot information')
            ->register('time', fn($b, $id, $args) => null, 'Get current time');

        $registered = $this->bot->commands()->getRegisteredCommands();

        $this->assertContains('start', $registered);
        $this->assertContains('help', $registered);
        $this->assertContains('ping', $registered);
        $this->assertContains('echo', $registered);
        $this->assertContains('info', $registered);
        $this->assertContains('time', $registered);
    }

    public function test_start_command_sends_welcome_message(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $this->bot->commands()->register('start', function ($bot, $chatId, $args) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => '👋 Welcome! Use /help to see available commands.',
                'parse_mode' => 'MarkdownV2',
            ]);
        }, 'Start the bot');

        $this->bot->commands()->handleUpdate([
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'chat' => ['id' => 123],
                'text' => '/start',
            ],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Welcome', $request['params']['text']);
        $this->assertSame('MarkdownV2', $request['params']['parse_mode']);
    }

    public function test_help_command_calls_sendHelp(): void
    {
        $this->mockClient->setResponse(['message_id' => 2]);

        $this->bot->commands()
            ->register('start', fn($b, $id, $a) => null, 'Start the bot')
            ->register('help', function ($bot, $chatId, $args) {
                $bot->commands()->sendHelp($chatId);
            }, 'Show help');

        $this->bot->commands()->handleUpdate([
            'update_id' => 2,
            'message' => [
                'message_id' => 2,
                'chat' => ['id' => 456],
                'text' => '/help',
            ],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('start', $request['params']['text']);
    }

    public function test_echo_command_echoes_arguments(): void
    {
        $this->mockClient->setResponse(['message_id' => 3]);
        $captured = null;

        $this->bot->commands()->register('echo', function ($bot, $chatId, $args) use (&$captured) {
            $text = implode(' ', $args);
            if (empty($text)) {
                $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Usage: /echo <text>']);
                return;
            }
            $captured = $text;
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "🔊 You said: {$text}",
                'parse_mode' => 'MarkdownV2',
            ]);
        }, 'Echo back');

        $this->bot->commands()->handleUpdate([
            'update_id' => 3,
            'message' => [
                'message_id' => 3,
                'chat' => ['id' => 789],
                'text' => '/echo hello world',
            ],
        ]);

        $this->assertSame('hello world', $captured);
    }

    public function test_empty_echo_sends_usage_message(): void
    {
        $this->mockClient->setResponse(['message_id' => 4]);

        $this->bot->commands()->register('echo', function ($bot, $chatId, $args) {
            $text = implode(' ', $args);
            if (empty($text)) {
                $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Usage: /echo <text to echo>']);
                return;
            }
            $bot->messages()->send(['chat_id' => $chatId, 'text' => "🔊 You said: {$text}"]);
        });

        $this->bot->commands()->handleUpdate([
            'update_id' => 4,
            'message' => ['message_id' => 4, 'chat' => ['id' => 100], 'text' => '/echo'],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Usage', $request['params']['text']);
    }

    public function test_middleware_runs_before_command(): void
    {
        $this->mockClient->setResponse(['message_id' => 5]);

        $log = [];

        $this->bot->commands()
            ->addMiddleware('logging', function ($bot, $chatId, $command, $args) use (&$log) {
                $log[] = "middleware: {$command}";
                return true;
            })
            ->register('ping', function ($bot, $chatId, $args) use (&$log) {
                $log[] = 'command: ping';
                $bot->messages()->send(['chat_id' => $chatId, 'text' => '🏓 Pong!']);
            }, 'Check ping');

        $this->bot->commands()->handleUpdate([
            'update_id' => 5,
            'message' => ['message_id' => 5, 'chat' => ['id' => 123], 'text' => '/ping'],
        ]);

        $this->assertSame('middleware: ping', $log[0]);
        $this->assertSame('command: ping', $log[1]);
    }

    public function test_middleware_returning_false_stops_execution(): void
    {
        $commandCalled = false;

        $this->bot->commands()
            ->addMiddleware('blocker', function ($bot, $chatId, $command, $args) {
                return false;
            })
            ->register('blocked', function ($bot, $chatId, $args) use (&$commandCalled) {
                $commandCalled = true;
            });

        $this->bot->commands()->handleUpdate([
            'update_id' => 6,
            'message' => ['message_id' => 6, 'chat' => ['id' => 123], 'text' => '/blocked'],
        ]);

        $this->assertFalse($commandCalled);
        $this->assertSame(0, $this->mockClient->getRequestCount());
    }

    public function test_default_handler_called_for_unknown_command(): void
    {
        $this->mockClient->setResponse(['message_id' => 6]);

        $this->bot->commands()->setDefault(function ($bot, $chatId, $command, $args) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "❓ Unknown command: /{$command}\n\nType /help to see available commands.",
            ]);
        });

        $this->bot->commands()->handleUpdate([
            'update_id' => 7,
            'message' => ['message_id' => 7, 'chat' => ['id' => 123], 'text' => '/unknown'],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Unknown command', $request['params']['text']);
    }

    public function test_non_command_message_is_not_handled(): void
    {
        $result = $this->bot->commands()->handleUpdate([
            'update_id' => 8,
            'message' => [
                'message_id' => 8,
                'chat' => ['id' => 123],
                'text' => 'Send /help to see available commands.',
            ],
        ]);

        $this->assertFalse($result);
        $this->assertSame(0, $this->mockClient->getRequestCount());
    }

    public function test_info_command_calls_getMe_via_api(): void
    {
        $this->mockClient->setResponse([
            'id' => 12345,
            'first_name' => 'TestBot',
            'username' => 'testbot',
            'is_bot' => true,
            'can_join_groups' => true,
            'can_read_all_group_messages' => false,
        ]);
        $this->mockClient->setResponse(['message_id' => 7]);

        $this->bot->commands()->register('info', function ($bot, $chatId, $args) {
            $me = $bot->api()->call(ApiMethod::GET_ME);
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "ℹ️ *Bot Information*\n\nName: {$me['first_name']}\nUsername: @{$me['username']}",
                'parse_mode' => 'MarkdownV2',
            ]);
        });

        $this->bot->commands()->handleUpdate([
            'update_id' => 9,
            'message' => ['message_id' => 9, 'chat' => ['id' => 123], 'text' => '/info'],
        ]);

        $this->assertSame(2, $this->mockClient->getRequestCount());
        $sentText = $this->mockClient->getLastRequest()['params']['text'];
        $this->assertStringContainsString('TestBot', $sentText);
    }

    public function test_hasCommand_returns_correct_result(): void
    {
        $this->bot->commands()
            ->register('start', fn($b, $id, $a) => null)
            ->register('help', fn($b, $id, $a) => null);

        $this->assertTrue($this->bot->commands()->hasCommand('start'));
        $this->assertTrue($this->bot->commands()->hasCommand('/start'));
        $this->assertTrue($this->bot->commands()->hasCommand('START'));
        $this->assertFalse($this->bot->commands()->hasCommand('unknown'));
    }

    public function test_generate_help_includes_registered_commands_with_descriptions(): void
    {
        $this->bot->commands()
            ->register('start', fn($b, $id, $a) => null, 'Start the bot and see this message')
            ->register('ping', fn($b, $id, $a) => null, 'Check bot response time');

        $help = $this->bot->commands()->generateHelp();

        $this->assertStringContainsString('/start', $help);
        $this->assertStringContainsString('Start the bot and see this message', $help);
        $this->assertStringContainsString('/ping', $help);
        $this->assertStringContainsString('Check bot response time', $help);
    }
}
