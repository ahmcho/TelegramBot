<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Bulk\BulkResult;
use AhmCho\Telegram\Bulk\BulkSendException;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

// BulkSendException is thrown when throwExceptions=true (default) and failures occur.
// Tests for failure scenarios use a bot configured with throwExceptions=false.

/**
 * Tests for examples/bulk-test.php
 *
 * Verifies the bulk messaging patterns demonstrated in the example:
 * - sendBulk() sends different messages to different chats
 * - broadcast() sends the same message to multiple chats
 * - BulkResult tracks success/failure counts and per-result details
 * - BulkSendException is thrown when all operations fail
 * - MarkdownV2 auto-escaping works in bulk send
 * - broadcast() with MarkdownV2 parse mode escapes text
 */
final class BulkExampleTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;
    private TelegramBot $botNoThrow;

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
        $configNoThrow = new BotConfig(token: 'test_token', loggingEnabled: false, throwExceptions: false);
        $this->botNoThrow = new TelegramBot(null, $configNoThrow, $this->mockClient);
    }

    public function test_sendBulk_sends_to_multiple_chats(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);
        $this->mockClient->setResponse(['message_id' => 2]);
        $this->mockClient->setResponse(['message_id' => 3]);

        $result = $this->bot->messages()->sendBulk([
            ['chat_id' => 111, 'text' => 'Message 1'],
            ['chat_id' => 222, 'text' => 'Message 2'],
            ['chat_id' => 333, 'text' => 'Message 3'],
        ]);

        $this->assertInstanceOf(BulkResult::class, $result);
        $this->assertSame(3, $result->total);
        $this->assertSame(3, $result->successful);
        $this->assertSame(0, $result->failed);
        $this->assertTrue($result->isSuccess());
    }

    public function test_sendBulk_counts_failures(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);
        $this->mockClient->setException(new HttpClientException('Network error'));
        $this->mockClient->setResponse(['message_id' => 3]);

        // Use botNoThrow so BulkSendException is not thrown on partial failure
        $result = $this->botNoThrow->messages()->sendBulk([
            ['chat_id' => 111, 'text' => 'OK message'],
            ['chat_id' => -999, 'text' => 'Bad chat'],
            ['chat_id' => 333, 'text' => 'Another OK'],
        ]);

        $this->assertSame(3, $result->total);
        $this->assertSame(2, $result->successful);
        $this->assertSame(1, $result->failed);
        $this->assertFalse($result->isSuccess());
    }

    public function test_bulk_result_has_per_result_details(): void
    {
        $this->mockClient->setResponse(['message_id' => 10]);
        $this->mockClient->setResponse(['message_id' => 11]);

        $result = $this->bot->messages()->sendBulk([
            ['chat_id' => 100, 'text' => 'Hi 100'],
            ['chat_id' => 200, 'text' => 'Hi 200'],
        ]);

        $this->assertCount(2, $result->results);
        $this->assertTrue($result->results[0]['success']);
        $this->assertSame(100, $result->results[0]['chat_id']);
        $this->assertSame(10, $result->results[0]['message_id']);
        $this->assertTrue($result->results[1]['success']);
        $this->assertSame(200, $result->results[1]['chat_id']);
    }

    public function test_broadcast_sends_same_message_to_multiple_chats(): void
    {
        $this->mockClient->setResponse(['message_id' => 20]);
        $this->mockClient->setResponse(['message_id' => 21]);
        $this->mockClient->setResponse(['message_id' => 22]);

        $chatIds = [111, 222, 333];
        $result = $this->bot->messages()->broadcast($chatIds, 'Announcement!');

        $this->assertInstanceOf(BulkResult::class, $result);
        $this->assertSame(3, $result->total);
        $this->assertSame(3, $result->successful);
    }

    public function test_broadcast_with_markdownv2_escapes_text(): void
    {
        $this->mockClient->setResponse(['message_id' => 30]);
        $this->mockClient->setResponse(['message_id' => 31]);

        $this->bot->messages()->broadcast(
            [100, 200],
            'Important! Great deal.',
            ['parse_mode' => 'MarkdownV2']
        );

        $requests = $this->mockClient->getRequests();
        foreach ($requests as $request) {
            $text = $request['params']['text'];
            $this->assertStringContainsString('\!', $text, 'Exclamation should be escaped');
            $this->assertStringContainsString('\.', $text, 'Period should be escaped');
        }
    }

    public function test_bulk_result_is_countable(): void
    {
        $this->mockClient->setResponse(['message_id' => 40]);
        $this->mockClient->setResponse(['message_id' => 41]);

        $result = $this->bot->messages()->sendBulk([
            ['chat_id' => 1, 'text' => 'msg1'],
            ['chat_id' => 2, 'text' => 'msg2'],
        ]);

        $this->assertCount(2, $result);
    }

    public function test_sendBulk_empty_array_returns_empty_result(): void
    {
        $result = $this->bot->messages()->sendBulk([]);

        $this->assertSame(0, $result->total);
        $this->assertSame(0, $result->successful);
        $this->assertSame(0, $result->failed);
        $this->assertTrue($result->isSuccess());
    }

    public function test_broadcast_empty_chat_list_returns_empty_result(): void
    {
        $result = $this->bot->messages()->broadcast([], 'Nobody receives this');

        $this->assertSame(0, $result->total);
    }

    public function test_bulk_send_raw_does_not_escape_markdown(): void
    {
        $this->mockClient->setResponse(['message_id' => 50]);
        $this->mockClient->setResponse(['message_id' => 51]);

        $formattedText = '*Bold announcement* — already formatted!';

        $this->bot->messages()->sendBulkRaw([
            ['chat_id' => 100, 'text' => $formattedText, 'parse_mode' => 'MarkdownV2'],
            ['chat_id' => 200, 'text' => $formattedText, 'parse_mode' => 'MarkdownV2'],
        ]);

        $requests = $this->mockClient->getRequests();
        foreach ($requests as $request) {
            $this->assertSame($formattedText, $request['params']['text']);
        }
    }

    public function test_bulk_result_errors_property_contains_error_messages(): void
    {
        $this->mockClient->setException(new HttpClientException('Forbidden'));
        $this->mockClient->setException(new HttpClientException('Chat not found'));

        // Use botNoThrow so BulkSendException is not thrown
        $result = $this->botNoThrow->messages()->sendBulk([
            ['chat_id' => -1, 'text' => 'bad1'],
            ['chat_id' => -2, 'text' => 'bad2'],
        ]);

        $this->assertSame(0, $result->successful);
        $this->assertSame(2, $result->failed);
        $this->assertCount(2, $result->errors);
    }

    public function test_sendBulk_result_chat_ids_match_input(): void
    {
        $this->mockClient->setResponse(['message_id' => 60]);
        $this->mockClient->setResponse(['message_id' => 61]);

        $messages = [
            ['chat_id' => 777, 'text' => 'msg to 777'],
            ['chat_id' => 888, 'text' => 'msg to 888'],
        ];

        $result = $this->bot->messages()->sendBulk($messages);

        $this->assertSame(777, $result->results[0]['chat_id']);
        $this->assertSame(888, $result->results[1]['chat_id']);
    }
}
