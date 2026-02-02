<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Integration;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Bulk\BulkOperationManager;

/**
 * Telegram API Integration Tests
 *
 * End-to-end workflow tests using mock HTTP client
 */
final class TelegramApiIntegrationTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $this->bot = new TelegramBot('test_token', null, $this->mockClient);
    }

    public function test_bot_initialization(): void
    {
        $this->mockClient->setResponse([
            'id' => 123456789,
            'is_bot' => true,
            'first_name' => 'TestBot',
            'username' => 'test_bot'
        ]);

        $me = $this->bot->getMe();

        $this->assertIsArray($me);
        $this->assertSame(123456789, $me['id']);
        $this->assertTrue($me['is_bot']);
        $this->assertSame('TestBot', $me['first_name']);
    }

    public function test_send_text_message_workflow(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 1,
            'from' => ['id' => 123456789, 'is_bot' => true, 'first_name' => 'TestBot'],
            'chat' => ['id' => 123456789, 'type' => 'private'],
            'date' => time(),
            'text' => 'Hello, World!'
        ]);

        $message = $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'Hello, World!'
        ]);

        $this->assertSame(1, $message['message_id']);
        $this->assertSame('Hello, World!', $message['text']);
    }

    public function test_send_message_with_inline_keyboard(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 2,
            'from' => ['id' => 123456789, 'is_bot' => true],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Choose an option:'
        ]);

        $keyboard = InlineKeyboardBuilder::create();
        $keyboard->addRow(
            Button::callback('Option 1', 'opt1'),
            Button::callback('Option 2', 'opt2')
        );

        $message = $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'Choose an option:',
            'reply_markup' => $keyboard->build()
        ]);

        $this->assertSame(2, $message['message_id']);

        // Verify the keyboard JSON is correct
        $request = $this->mockClient->getLastRequest();
        $this->assertArrayHasKey('reply_markup', $request['params']);
        $keyboardData = json_decode($request['params']['reply_markup'], true);
        $this->assertIsArray($keyboardData);
        $this->assertArrayHasKey('inline_keyboard', $keyboardData);
        $this->assertCount(1, $keyboardData['inline_keyboard']);
        $this->assertCount(2, $keyboardData['inline_keyboard'][0]);
    }

    public function test_send_message_with_reply_keyboard(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 3,
            'from' => ['id' => 123456789, 'is_bot' => true],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Click a button:'
        ]);

        $options = new ReplyKeyboardOptions(resizeKeyboard: true, oneTimeKeyboard: false);
        $keyboard = ReplyKeyboardBuilder::create($options);
        $keyboard->addRow(
            Button::text('Button 1'),
            Button::text('Button 2')
        );

        $message = $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'Click a button:',
            'reply_markup' => $keyboard->build()
        ]);

        $this->assertSame(3, $message['message_id']);

        // Verify the keyboard JSON is correct
        $request = $this->mockClient->getLastRequest();
        $this->assertArrayHasKey('reply_markup', $request['params']);
        $keyboardData = json_decode($request['params']['reply_markup'], true);
        $this->assertIsArray($keyboardData);
        $this->assertArrayHasKey('keyboard', $keyboardData);
        $this->assertArrayHasKey('resize_keyboard', $keyboardData);
        $this->assertTrue($keyboardData['resize_keyboard']);
        $this->assertCount(1, $keyboardData['keyboard']);
        $this->assertCount(2, $keyboardData['keyboard'][0]);
    }

    public function test_send_photo_workflow(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 4,
            'photo' => [
                ['file_id' => 'abc123', 'file_size' => 1234, 'width' => 800, 'height' => 600]
            ]
        ]);

        $message = $this->bot->media()->sendPhoto([
            'chat_id' => 123456789,
            'photo' => 'https://example.com/photo.jpg',
            'caption' => 'Test photo'
        ]);

        $this->assertSame(4, $message['message_id']);
        $this->assertArrayHasKey('photo', $message);
    }

    public function test_chat_operations_workflow(): void
    {
        // Get chat info
        $this->mockClient->setResponse([
            'id' => 123456789,
            'type' => 'group',
            'title' => 'Test Group'
        ]);

        $chat = $this->bot->chats()->getChat(['chat_id' => 123456789]);
        $this->assertSame('group', $chat['type']);

        // Get member count
        $this->mockClient->setIntResponse(42);
        $count = $this->bot->chats()->getMemberCount(['chat_id' => 123456789]);
        $this->assertSame(42, $count);
        $this->assertIsInt($count);

        // Send chat action
        $this->mockClient->setBoolResponse(true);
        $result = $this->bot->chats()->sendAction([
            'chat_id' => 123456789,
            'action' => 'typing'
        ]);
        $this->assertTrue($result);
    }

    public function test_edit_message_workflow(): void
    {
        // Send initial message
        $this->mockClient->setResponse([
            'message_id' => 5,
            'from' => ['id' => 123456789, 'is_bot' => true],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Original text'
        ]);

        $message = $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'Original text'
        ]);

        // Edit the message
        $this->mockClient->setResponse([
            'message_id' => 5,
            'from' => ['id' => 123456789, 'is_bot' => true],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Updated text'
        ]);

        $updated = $this->bot->messages()->editText([
            'chat_id' => 123456789,
            'message_id' => $message['message_id'],
            'text' => 'Updated text'
        ]);

        $this->assertSame('Updated text', $updated['text']);
    }

    public function test_delete_message_workflow(): void
    {
        // Send message
        $this->mockClient->setResponse([
            'message_id' => 6,
            'from' => ['id' => 123456789, 'is_bot' => true],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'To be deleted'
        ]);

        $message = $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'To be deleted'
        ]);

        // Delete the message
        $this->mockClient->setBoolResponse(true);
        $result = $this->bot->messages()->delete([
            'chat_id' => 123456789,
            'message_id' => $message['message_id']
        ]);

        $this->assertTrue($result);
    }

    public function test_forward_message_workflow(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 7,
            'from' => ['id' => 123456789, 'is_bot' => true],
            'chat' => ['id' => 987654321],
            'date' => time(),
            'text' => 'Forwarded message'
        ]);

        $message = $this->bot->messages()->forward([
            'chat_id' => 987654321,
            'from_chat_id' => 123456789,
            'message_id' => 1
        ]);

        $this->assertSame(7, $message['message_id']);
        $this->assertSame(987654321, $message['chat']['id']);
    }

    public function test_webhook_operations_workflow(): void
    {
        // Set webhook
        $this->mockClient->setBoolResponse(true);
        $result = $this->bot->webhooks()->set([
            'url' => 'https://example.com/webhook'
        ]);
        $this->assertTrue($result);

        // Get webhook info
        $this->mockClient->setResponse([
            'url' => 'https://example.com/webhook',
            'has_custom_certificate' => false,
            'pending_update_count' => 0
        ]);
        $info = $this->bot->webhooks()->getInfo();
        $this->assertSame('https://example.com/webhook', $info['url']);

        // Delete webhook
        $this->mockClient->setBoolResponse(true);
        $result = $this->bot->webhooks()->delete();
        $this->assertTrue($result);
    }

    public function test_bulk_messaging_workflow(): void
    {
        $chatIds = [111, 222, 333];

        // Set up responses for each chat ID
        foreach ($chatIds as $chatId) {
            $this->mockClient->setResponse([
                'message_id' => $chatId,
                'from' => ['id' => 123456789, 'is_bot' => true],
                'chat' => ['id' => $chatId],
                'date' => time(),
                'text' => 'Bulk message'
            ]);
        }

        // Access bulk manager through API service
        $result = $this->bot->api()->getBulkManager()->broadcast(
            ApiMethod::SEND_MESSAGE,
            $chatIds,
            ['text' => 'Bulk message']
        );

        $this->assertSame(3, $result->total);
        $this->assertSame(3, $result->successful);
        $this->assertSame(0, $result->failed);
        $this->assertFalse($result->hasFailures());

        // Verify individual results
        $results = $result->results;
        $this->assertCount(3, $results);
        $this->assertArrayHasKey('success', $results[0]);
        $this->assertArrayHasKey('chat_id', $results[0]);
        $this->assertTrue($results[0]['success']);
    }

    public function test_error_handling_workflow(): void
    {
        // Test with API error response
        $this->mockClient->setException(new \Exception('Telegram API error: Bad Request'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Telegram API error: Bad Request');

        $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'Test'
        ]);
    }

    public function test_markdown_v2_auto_escaping_workflow(): void
    {
        $this->mockClient->setResponse([
            'message_id' => 8,
            'from' => ['id' => 123456789, 'is_bot' => true],
            'chat' => ['id' => 123456789],
            'date' => time(),
            'text' => 'Test_text'
        ]);

        $message = $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'Test_text',
            'parse_mode' => 'MarkdownV2'
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame('Test\_text', $request['params']['text']);
    }

    public function test_multiple_consecutive_requests(): void
    {
        // Request 1: getMe
        $this->mockClient->setResponse([
            'id' => 123456789,
            'is_bot' => true,
            'first_name' => 'TestBot'
        ]);
        $me = $this->bot->getMe();
        $this->assertSame('TestBot', $me['first_name']);

        // Request 2: sendMessage
        $this->mockClient->setResponse([
            'message_id' => 9,
            'text' => 'Hello'
        ]);
        $msg = $this->bot->messages()->send([
            'chat_id' => 123456789,
            'text' => 'Hello'
        ]);
        $this->assertSame('Hello', $msg['text']);

        // Request 3: getChat
        $this->mockClient->setResponse([
            'id' => 123456789,
            'type' => 'private'
        ]);
        $chat = $this->bot->chats()->getChat(['chat_id' => 123456789]);
        $this->assertSame('private', $chat['type']);

        $this->assertSame(3, $this->mockClient->getRequestCount());
    }
}
