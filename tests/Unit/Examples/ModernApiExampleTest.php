<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for the modern service-oriented API patterns used across the examples:
 * - Sending messages via $bot->messages()->send()
 * - Sending photos via $bot->media()->sendPhoto()
 * - Using formatters for text styling
 * - Building inline keyboards with Button helpers
 * - MarkdownV2 auto-escaping in action
 * - Enum-based API method routing
 */
final class ModernApiExampleTest extends TestCase
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

    public function test_send_message_via_service_accessor(): void
    {
        $this->mockClient->setResponse(['message_id' => 1, 'chat' => ['id' => 123]]);

        $result = $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => 'Hello World!',
        ]);

        $this->assertSame(1, $result['message_id']);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_send_message_with_markdown_v2_auto_escaping(): void
    {
        $this->mockClient->setResponse(['message_id' => 2, 'chat' => ['id' => 123]]);

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => 'Price is 9.99 (special!) and note.',
            'parse_mode' => 'MarkdownV2',
        ]);

        $request = $this->mockClient->getLastRequest();
        $sentText = $request['params']['text'];

        $this->assertStringContainsString('\.', $sentText, 'Period should be escaped');
        $this->assertStringContainsString('\(', $sentText, 'Opening paren should be escaped');
        $this->assertStringContainsString('\)', $sentText, 'Closing paren should be escaped');
        $this->assertStringContainsString('\!', $sentText, 'Exclamation should be escaped');
    }

    public function test_send_photo_via_media_service(): void
    {
        $this->mockClient->setResponse(['message_id' => 3, 'photo' => [['file_id' => 'photo123']]]);

        $result = $this->bot->media()->sendPhoto([
            'chat_id' => 123,
            'photo' => 'https://example.com/image.jpg',
            'caption' => 'My photo',
        ]);

        $this->assertArrayHasKey('message_id', $result);
        $request = $this->mockClient->getLastRequest();
        $this->assertSame('https://example.com/image.jpg', $request['params']['photo']);
        $this->assertSame('My photo', $request['params']['caption']);
    }

    public function test_formatter_produces_bold_text(): void
    {
        $formatter = $this->bot->formatter();

        $this->assertInstanceOf(MarkdownV2Formatter::class, $formatter);

        $bold = $formatter->bold('Hello');
        $this->assertStringContainsString('Hello', $bold);
        $this->assertStringContainsString('*', $bold);
    }

    public function test_formatter_combined_with_send(): void
    {
        $this->mockClient->setResponse(['message_id' => 4]);

        $text = $this->bot->formatter()->bold('Welcome!')
            . "\n\nPlain text follows.";

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Welcome', $request['params']['text']);
    }

    public function test_inline_keyboard_with_buttons_is_sent_in_reply_markup(): void
    {
        $this->mockClient->setResponse(['message_id' => 5]);

        $builder = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('Option A', 'action_a'),
                Button::callback('Option B', 'action_b')
            )
            ->addRow(
                Button::url('Visit Website', 'https://example.com')
            );

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => 'Choose an option:',
            'reply_markup' => $builder->build(),
        ]);

        $markup = $builder->toArray();

        $this->assertArrayHasKey('inline_keyboard', $markup);
        $this->assertCount(2, $markup['inline_keyboard']);

        $firstRow = $markup['inline_keyboard'][0];
        $this->assertSame('Option A', $firstRow[0]['text']);
        $this->assertSame('action_a', $firstRow[0]['callback_data']);
        $this->assertSame('Option B', $firstRow[1]['text']);

        $secondRow = $markup['inline_keyboard'][1];
        $this->assertSame('Visit Website', $secondRow[0]['text']);
        $this->assertSame('https://example.com', $secondRow[0]['url']);
    }

    public function test_api_direct_call_with_enum(): void
    {
        $this->mockClient->setResponse([
            'id' => 12345,
            'first_name' => 'Test Bot',
            'username' => 'testbot',
            'is_bot' => true,
        ]);

        $result = $this->bot->api()->call(ApiMethod::GET_ME);

        $this->assertSame(12345, $result['id']);
        $this->assertSame('Test Bot', $result['first_name']);
        $this->assertTrue($result['is_bot']);
    }

    public function test_sendRaw_does_not_escape_markdown(): void
    {
        $this->mockClient->setResponse(['message_id' => 6]);

        $formattedText = '*Bold Text* and _italic_';
        $this->bot->messages()->sendRaw([
            'chat_id' => 123,
            'text' => $formattedText,
            'parse_mode' => 'MarkdownV2',
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertSame($formattedText, $request['params']['text']);
    }

    public function test_send_versus_sendRaw_escaping_difference(): void
    {
        $rawText = '100% done!';

        $this->mockClient->setResponse(['message_id' => 7]);
        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $rawText,
            'parse_mode' => 'MarkdownV2',
        ]);
        $sentText = $this->mockClient->getLastRequest()['params']['text'];

        $this->mockClient->setResponse(['message_id' => 8]);
        $this->bot->messages()->sendRaw([
            'chat_id' => 123,
            'text' => $rawText,
            'parse_mode' => 'MarkdownV2',
        ]);
        $rawSentText = $this->mockClient->getLastRequest()['params']['text'];

        $this->assertNotSame($sentText, $rawSentText, 'send() and sendRaw() should differ for special chars');
        $this->assertSame($rawText, $rawSentText, 'sendRaw() should not modify the text');
        $this->assertStringContainsString('\!', $sentText, 'send() should escape !');
    }

    public function test_botconfig_full_api_url_is_correct(): void
    {
        $config = new BotConfig(token: '123:ABC');

        $this->assertSame(
            'https://api.telegram.org/bot123:ABC/',
            $config->getFullApiUrl()
        );
    }

    public function test_botconfig_immutability_with_fluent_builders(): void
    {
        $original = new BotConfig(token: 'test_token', loggingEnabled: false, timeout: 30);
        $modified = $original->withTimeout(60);

        $this->assertSame(30, $original->getTimeout(), 'Original should not change');
        $this->assertSame(60, $modified->getTimeout(), 'New config should have new timeout');
    }

    public function test_multiple_messages_to_different_chats(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);
        $this->mockClient->setResponse(['message_id' => 2]);
        $this->mockClient->setResponse(['message_id' => 3]);

        $chatIds = [111, 222, 333];
        foreach ($chatIds as $chatId) {
            $this->bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "Hello chat {$chatId}!",
            ]);
        }

        $this->assertSame(3, $this->mockClient->getRequestCount());

        $requests = $this->mockClient->getRequests();
        $this->assertSame(111, $requests[0]['params']['chat_id']);
        $this->assertSame(222, $requests[1]['params']['chat_id']);
        $this->assertSame(333, $requests[2]['params']['chat_id']);
    }
}
