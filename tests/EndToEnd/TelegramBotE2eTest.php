<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\EndToEnd;

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use PHPUnit\Framework\TestCase;

/**
 * End-to-End Tests for Telegram Bot
 *
 * These tests require a real bot token and make actual API calls.
 * They should be run manually with TELEGRAM_BOT_TOKEN set.
 *
 * Run with: TELEGRAM_BOT_TOKEN=your_token vendor/bin/phpunit tests/EndToEnd/
 */
class TelegramBotE2eTest extends TestCase
{
    private ?string $botToken;
    private ?string $testChatId;
    private ?TelegramBot $bot;

    protected function setUp(): void
    {
        $this->botToken = getenv('TELEGRAM_BOT_TOKEN');
        $this->testChatId = getenv('TEST_CHAT_ID');

        // Skip tests if no token provided
        if (!$this->botToken) {
            $this->markTestSkipped('TELEGRAM_BOT_TOKEN environment variable not set');
        }

        if (!$this->testChatId) {
            $this->markTestSkipped('TEST_CHAT_ID environment variable not set');
        }

        $config = new BotConfig(
            token: $this->botToken,
            throwExceptions: true
        );

        $this->bot = new TelegramBot(null, $config);
    }

    /**
     * Test basic bot connection with getMe
     */
    public function testGetMeReturnsValidBotInfo(): void
    {
        $result = $this->bot->getMe();

        $this->assertIsArray($result);
        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
        $this->assertIsArray($result['result']);
        $this->assertArrayHasKey('id', $result['result']);
        $this->assertArrayHasKey('is_bot', $result['result']);
        $this->assertTrue($result['result']['is_bot']);
        $this->assertArrayHasKey('username', $result['result']);
    }

    /**
     * Test sending a text message
     */
    public function testSendMessageSucceeds(): void
    {
        $testText = 'E2E Test Message ' . time();

        $result = $this->bot->messages()->send([
            'chat_id' => $this->testChatId,
            'text' => $testText
        ]);

        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('message_id', $result['result']);
    }

    /**
     * Test sending message with MarkdownV2 formatting
     */
    public function testSendMessageWithMarkdownV2Formatting(): void
    {
        $testText = '*Bold* and _italic_ text';

        $result = $this->bot->messages()->send([
            'chat_id' => $this->testChatId,
            'text' => $testText,
            'parse_mode' => 'MarkdownV2'
        ]);

        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
    }

    /**
     * Test sending message with HTML formatting
     */
    public function testSendMessageWithHTMLFormatting(): void
    {
        $testText = '<b>Bold</b> and <i>italic</i> text';

        $result = $this->bot->messages()->send([
            'chat_id' => $this->testChatId,
            'text' => $testText,
            'parse_mode' => 'HTML'
        ]);

        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
    }

    /**
     * Test editing a message
     */
    public function testEditMessageSucceeds(): void
    {
        // First send a message
        $sendResult = $this->bot->messages()->send([
            'chat_id' => $this->testChatId,
            'text' => 'Original message'
        ]);

        $messageId = $sendResult['result']['message_id'];

        // Wait a moment
        sleep(1);

        // Then edit it
        $editResult = $this->bot->messages()->editText([
            'chat_id' => $this->testChatId,
            'message_id' => $messageId,
            'text' => 'Edited message'
        ]);

        $this->assertTrue($editResult['ok'] ?? false);
    }

    /**
     * Test deleting a message
     */
    public function testDeleteMessageSucceeds(): void
    {
        // First send a message
        $sendResult = $this->bot->messages()->send([
            'chat_id' => $this->testChatId,
            'text' => 'Message to delete'
        ]);

        $messageId = $sendResult['result']['message_id'];

        // Wait a moment
        sleep(1);

        // Then delete it
        $deleteResult = $this->bot->messages()->delete([
            'chat_id' => $this->testChatId,
            'message_id' => $messageId
        ]);

        $this->assertTrue($deleteResult['ok'] ?? false);
    }

    /**
     * Test sending message with inline keyboard
     */
    public function testSendMessageWithInlineKeyboard(): void
    {
        $keyboard = \AhmCho\Telegram\Keyboard\InlineKeyboardBuilder::create()
            ->addRow(
                \AhmCho\Telegram\Keyboard\Button::callback('Button 1', 'data_1'),
                \AhmCho\Telegram\Keyboard\Button::callback('Button 2', 'data_2')
            )
            ->build();

        $result = $this->bot->messages()->send([
            'chat_id' => $this->testChatId,
            'text' => 'Message with inline keyboard',
            'reply_markup' => $keyboard
        ]);

        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
    }

    /**
     * Test sending message with reply keyboard
     */
    public function testSendMessageWithReplyKeyboard(): void
    {
        $keyboard = \AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder::create()
            ->addButton('Option 1')
            ->addButton('Option 2')
            ->setOptions(
                \AhmCho\Telegram\Keyboard\ReplyKeyboardOptions::create()
                    ->resize()
                    ->oneTime()
            )
            ->build();

        $result = $this->bot->messages()->send([
            'chat_id' => $this->testChatId,
            'text' => 'Message with reply keyboard',
            'reply_markup' => $keyboard
        ]);

        $this->assertTrue($result['ok'] ?? false);
    }

    /**
     * Test getting chat information
     */
    public function testGetChatSucceeds(): void
    {
        $result = $this->bot->chats()->getChat([
            'chat_id' => $this->testChatId
        ]);

        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('id', $result['result']);
        $this->assertArrayHasKey('type', $result['result']);
    }

    /**
     * Test error handling with invalid chat ID
     */
    public function testErrorHandlingWithInvalidChatId(): void
    {
        $this->expectException(\AhmCho\Telegram\Exception\ApiException::class);

        $this->bot->messages()->send([
            'chat_id' => 'invalid_chat_id',
            'text' => 'Test'
        ]);
    }

    /**
     * Test command handler registration
     */
    public function testCommandHandlerRegistration(): void
    {
        $executed = false;

        $this->bot->commands()
            ->register('test', function ($bot, $chatId, $args) use (&$executed) {
                $executed = true;
                return $bot->messages()->send([
                    'chat_id' => $chatId,
                    'text' => 'Test command executed'
                ]);
            });

        // Manually trigger the command
        $this->bot->commands()->execute('test', (int) $this->testChatId);

        $this->assertTrue($executed);
    }

    /**
     * Test retry logic with valid scenario
     */
    public function testRetryWithInvalidChatId(): void
    {
        $this->expectException(\AhmCho\Telegram\Exception\ApiException::class);

        $this->bot->sendMessageWithRetry(
            [
                'chat_id' => 999999999,
                'text' => 'Test'
            ],
            ['max_retries' => 1]
        );
    }

    /**
     * Test getUpdates (long polling)
     */
    public function testGetUpdatesReturnsArray(): void
    {
        // Use timeout to avoid long wait
        $result = $this->bot->getUpdates(['timeout' => 1]);

        $this->assertIsArray($result);
        // May be empty if no updates, but should be array
    }

    /**
     * Test webhook info
     */
    public function testGetWebhookInfoSucceeds(): void
    {
        $result = $this->bot->webhooks()->getInfo();

        $this->assertIsArray($result);
        $this->assertTrue($result['ok'] ?? false);
        $this->assertArrayHasKey('result', $result);
    }

    /**
     * Test service accessors return correct types
     */
    public function testServiceAccessorsReturnCorrectTypes(): void
    {
        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\MessageService::class, $this->bot->messages());
        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\MediaService::class, $this->bot->media());
        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\ChatService::class, $this->bot->chats());
        $this->assertInstanceOf(\AhmCho\Telegram\Api\Methods\WebhookService::class, $this->bot->webhooks());
        $this->assertInstanceOf(\AhmCho\Telegram\Command\CommandHandler::class, $this->bot->commands());
    }

    /**
     * Test getWebhookUpdates with no data
     */
    public function testGetWebhookUpdatesWithNoDataReturnsNull(): void
    {
        $result = $this->bot->getWebhookUpdates();
        // Should return null when no webhook data present
        $this->assertNull($result);
    }

    /**
     * Clean up test messages
     */
    protected function tearDown(): void
    {
        // Clean up any test artifacts if needed
        parent::tearDown();
    }
}
