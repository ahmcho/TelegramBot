<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Command;

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Command\CommandHandler;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for CommandHandler
 */
class CommandHandlerTest extends TestCase
{
    private CommandHandler $commandHandler;
    private TelegramBot $mockBot;

    protected function setUp(): void
    {
        // Create a mock bot for testing
        $this->mockBot = $this->createMockBot();
        $this->commandHandler = new CommandHandler($this->mockBot);
    }

    public function testRegisterSingleCommand(): void
    {
        $executed = false;
        $chatId = 123;

        $this->commandHandler->register('test', function ($bot, $id, $args) use (&$executed, $chatId) {
            $executed = true;
            $this->assertSame($this->mockBot, $bot);
            $this->assertSame($chatId, $id);
            $this->assertIsArray($args);
        });

        $update = $this->createUpdate($chatId, '/test');
        $result = $this->commandHandler->handleUpdate($update);

        $this->assertTrue($result);
        $this->assertTrue($executed);
    }

    public function testRegisterCommandWithDescription(): void
    {
        $this->commandHandler->register('start', function () {}, 'Start the bot');

        $help = $this->commandHandler->generateHelp();

        $this->assertStringContainsString('/start - Start the bot', $help);
    }

    public function testRegisterMultipleCommands(): void
    {
        $this->commandHandler
            ->register('help', function () {})
            ->register('start', function () {})
            ->register('ping', function () {});

        $commands = $this->commandHandler->getRegisteredCommands();

        $this->assertCount(3, $commands);
        $this->assertContains('help', $commands);
        $this->assertContains('start', $commands);
        $this->assertContains('ping', $commands);
    }

    public function testRegisterCommandsFromArray(): void
    {
        $commands = [
            'help' => function () {},
            'start' => ['callback' => function () {}, 'description' => 'Start'],
            'ping' => function () {},
        ];

        $this->commandHandler->registerCommands($commands);

        $this->assertCount(3, $this->commandHandler->getRegisteredCommands());
        $help = $this->commandHandler->generateHelp();
        $this->assertStringContainsString('/start - Start', $help);
    }

    public function testCommandNormalization(): void
    {
        $executed = false;

        // Register without slash
        $this->commandHandler->register('test', function () use (&$executed) {
            $executed = true;
        });

        // Call with slash
        $update = $this->createUpdate(123, '/Test');
        $this->commandHandler->handleUpdate($update);

        $this->assertTrue($executed);
    }

    public function testCommandWithArguments(): void
    {
        $receivedArgs = [];

        $this->commandHandler->register('echo', function ($bot, $chatId, $args) use (&$receivedArgs) {
            $receivedArgs = $args;
        });

        $update = $this->createUpdate(123, '/echo hello world');
        $this->commandHandler->handleUpdate($update);

        $this->assertSame(['hello', 'world'], $receivedArgs);
    }

    public function testDefaultHandler(): void
    {
        $executed = false;

        $this->commandHandler->setDefault(function ($bot, $chatId, $command, $args) use (&$executed) {
            $executed = true;
            $this->assertSame('unknown', $command);
        });

        $update = $this->createUpdate(123, '/unknown');
        $result = $this->commandHandler->handleUpdate($update);

        $this->assertTrue($result);
        $this->assertTrue($executed);
    }

    public function testHandleUpdateReturnsFalseForNonMessage(): void
    {
        $update = ['callback_query' => ['id' => '123']];
        $result = $this->commandHandler->handleUpdate($update);

        $this->assertFalse($result);
    }

    public function testHandleUpdateReturnsFalseForNonCommandText(): void
    {
        $update = $this->createUpdate(123, 'regular text');
        $result = $this->commandHandler->handleUpdate($update);

        $this->assertFalse($result);
    }

    public function testMiddlewareExecution(): void
    {
        $middlewareExecuted = false;

        $this->commandHandler
            ->register('test', function () {})
            ->addMiddleware('logging', function ($bot, $chatId, $command, $args) use (&$middlewareExecuted) {
                $middlewareExecuted = true;
                return true;
            });

        $update = $this->createUpdate(123, '/test');
        $this->commandHandler->handleUpdate($update);

        $this->assertTrue($middlewareExecuted);
    }

    public function testMiddlewareCanStopExecution(): void
    {
        $commandExecuted = false;

        $this->commandHandler
            ->register('test', function () use (&$commandExecuted) {
                $commandExecuted = true;
            })
            ->addMiddleware('block', function () {
                return false; // Stop execution
            });

        $update = $this->createUpdate(123, '/test');
        $this->commandHandler->handleUpdate($update);

        $this->assertFalse($commandExecuted);
    }

    public function testGenerateHelp(): void
    {
        $this->commandHandler
            ->register('help', function () {}, 'Show help')
            ->register('start', function () {}, 'Start the bot')
            ->register('ping', function () {}, 'Check responsiveness');

        $help = $this->commandHandler->generateHelp();

        $this->assertStringContainsString('/help - Show help', $help);
        $this->assertStringContainsString('/start - Start the bot', $help);
        $this->assertStringContainsString('/ping - Check responsiveness', $help);
    }

    public function testGenerateHelpWithNoCommands(): void
    {
        $help = $this->commandHandler->generateHelp();

        $this->assertStringContainsString('No commands registered', $help);
    }

    public function testSendHelp(): void
    {
        $this->commandHandler
            ->register('help', function () {}, 'Show help');

        // This would call the actual bot, which we mock
        // For this test, we just verify the method exists
        $this->assertIsCallable([$this->commandHandler, 'sendHelp']);
    }

    public function testHasCommand(): void
    {
        $this->commandHandler->register('test', function () {});

        $this->assertTrue($this->commandHandler->hasCommand('test'));
        $this->assertTrue($this->commandHandler->hasCommand('/test'));
        $this->assertTrue($this->commandHandler->hasCommand('TEST'));
        $this->assertFalse($this->commandHandler->hasCommand('unknown'));
    }

    public function testUnregisterCommand(): void
    {
        $this->commandHandler->register('test', function () {}, 'Test command');

        $this->assertTrue($this->commandHandler->hasCommand('test'));

        $result = $this->commandHandler->unregister('test');

        $this->assertTrue($result);
        $this->assertFalse($this->commandHandler->hasCommand('test'));
    }

    public function testUnregisterNonExistentCommand(): void
    {
        $result = $this->commandHandler->unregister('nonexistent');

        $this->assertFalse($result);
    }

    public function testClearCommands(): void
    {
        $this->commandHandler
            ->register('test1', function () {})
            ->register('test2', function () {});

        $this->assertCount(2, $this->commandHandler->getRegisteredCommands());

        $this->commandHandler->clear();

        $this->assertCount(0, $this->commandHandler->getRegisteredCommands());
    }

    public function testChaining(): void
    {
        $result = $this->commandHandler
            ->register('test1', function () {})
            ->register('test2', function () {})
            ->register('test3', function () {});

        $this->assertSame($this->commandHandler, $result);
        $this->assertCount(3, $this->commandHandler->getRegisteredCommands());
    }

    /**
     * Create a mock TelegramBot for testing
     */
    private function createMockBot(): TelegramBot
    {
        // We'll create a partial mock or use a test double
        // For now, we'll create a real instance but won't use actual API calls
        return new class {
            public function messages() {
                return new class {
                    public function send() {
                        return ['message_id' => 123];
                    }
                };
            }
        };
    }

    /**
     * Create a mock update for testing
     */
    private function createUpdate(int $chatId, string $text): array
    {
        return [
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'chat' => [
                    'id' => $chatId,
                    'type' => 'private'
                ],
                'text' => $text
            ]
        ];
    }
}
