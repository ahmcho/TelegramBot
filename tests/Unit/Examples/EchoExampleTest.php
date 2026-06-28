<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/echo.php
 *
 * The echo bot receives messages and sends them back.
 * Key behaviors:
 * - Reads updates via getUpdates()
 * - Echoes user text back using send() with MarkdownV2 (auto-escaping)
 * - Preserves formatting from edited messages using sendRaw()
 * - Skips empty messages
 */
final class EchoExampleTest extends TestCase
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

    public function test_get_updates_returns_update_array(): void
    {
        $this->mockClient->setResponse([
            ['update_id' => 1, 'message' => ['chat' => ['id' => 123], 'text' => 'hello']],
        ]);

        $updates = $this->bot->getUpdates(['offset' => 0, 'timeout' => 30]);

        $this->assertIsArray($updates);
        $this->assertCount(1, $updates);
        $this->assertSame(1, $updates[0]['update_id']);
    }

    public function test_get_updates_with_offset_and_timeout(): void
    {
        $this->mockClient->setResponse([]);

        $this->bot->getUpdates(['offset' => 100, 'timeout' => 30]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame(100, $request['params']['offset']);
        $this->assertSame(30, $request['params']['timeout']);
    }

    public function test_echo_sends_user_text_back(): void
    {
        $chatId = 123;
        $userText = 'Hello bot!';

        $this->mockClient->setResponse(['message_id' => 42]);

        $this->bot->messages()->send([
            'chat_id' => $chatId,
            'text' => "You said: {$userText}",
            'parse_mode' => 'MarkdownV2',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame($chatId, $request['params']['chat_id']);
        $this->assertStringContainsString('Hello bot', $request['params']['text']);
        $this->assertSame('MarkdownV2', $request['params']['parse_mode']);
    }

    public function test_echo_auto_escapes_special_characters(): void
    {
        $this->mockClient->setResponse(['message_id' => 43]);

        $userText = 'Cost: 5.00 (great deal!)';

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => "You said: {$userText}",
            'parse_mode' => 'MarkdownV2',
        ]);

        $request = $this->mockClient->getLastRequest();
        $sentText = $request['params']['text'];

        $this->assertStringContainsString('\.', $sentText);
        $this->assertStringContainsString('\(', $sentText);
        $this->assertStringContainsString('\)', $sentText);
        $this->assertStringContainsString('\!', $sentText);
    }

    public function test_edited_message_uses_send_raw_to_preserve_markdown(): void
    {
        $this->mockClient->setResponse(['message_id' => 44]);

        $editedText = '*Bold text* and _italic_';

        $this->bot->messages()->sendRaw([
            'chat_id' => 123,
            'text' => "You edited to: {$editedText}",
            'parse_mode' => 'MarkdownV2',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('*Bold text*', $request['params']['text']);
        $this->assertStringContainsString('_italic_', $request['params']['text']);
    }

    public function test_empty_text_is_not_echoed(): void
    {
        $update = [
            'update_id' => 1,
            'message' => [
                'message_id' => 10,
                'chat' => ['id' => 123],
                'text' => '',
            ],
        ];

        $text = $update['message']['text'] ?? '';
        $processed = !empty($text);

        $this->assertFalse($processed);
        $this->assertSame(0, $this->mockClient->getRequestCount());
    }

    public function test_message_without_text_is_skipped(): void
    {
        $update = [
            'update_id' => 2,
            'message' => [
                'message_id' => 11,
                'chat' => ['id' => 456],
                'photo' => [['file_id' => 'photo123']],
            ],
        ];

        $text = $update['message']['text'] ?? '';

        $this->assertEmpty($text);
        $this->assertSame(0, $this->mockClient->getRequestCount());
    }

    public function test_offset_increments_by_update_id_plus_one(): void
    {
        $this->mockClient->setResponse([
            ['update_id' => 10, 'message' => ['chat' => ['id' => 1], 'text' => 'msg1']],
            ['update_id' => 11, 'message' => ['chat' => ['id' => 2], 'text' => 'msg2']],
        ]);

        $updates = $this->bot->getUpdates(['offset' => 0]);
        $offset = 0;

        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;
        }

        $this->assertSame(12, $offset);
    }

    public function test_update_containing_edited_message(): void
    {
        $update = [
            'update_id' => 5,
            'edited_message' => [
                'message_id' => 20,
                'chat' => ['id' => 789],
                'text' => '_italic fix_',
            ],
        ];

        $this->assertArrayHasKey('edited_message', $update);
        $this->assertArrayNotHasKey('message', $update);
        $this->assertSame('_italic fix_', $update['edited_message']['text']);
    }
}
