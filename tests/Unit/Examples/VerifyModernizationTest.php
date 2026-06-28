<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Bot\BotFactory;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Config\EnvLoader;
use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Api\Methods\MessageService;
use AhmCho\Telegram\Api\Methods\MediaService;
use AhmCho\Telegram\Api\Methods\ChatService;
use AhmCho\Telegram\Api\Methods\WebhookService;
use AhmCho\Telegram\Api\Methods\PollsService;
use AhmCho\Telegram\Api\Methods\InlineService;
use AhmCho\Telegram\Api\Methods\TopicsService;
use AhmCho\Telegram\Api\Methods\InviteLinksService;
use AhmCho\Telegram\Bulk\BulkOperationManager;
use AhmCho\Telegram\Bulk\BulkResult;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Client\CurlHttpClient;
use AhmCho\Telegram\Client\StreamHttpClient;
use AhmCho\Telegram\Client\HttpClientFactory;
use AhmCho\Telegram\Command\CommandHandler;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;
use AhmCho\Telegram\Formatting\HtmlFormatter;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Enums\ParseMode;
use AhmCho\Telegram\Enums\ChatAction;
use AhmCho\Telegram\Exception\TelegramException;
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Logging\LoggerFactory;
use AhmCho\Telegram\Logging\Logger;
use AhmCho\Telegram\Logging\NullLogger;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/verify-modernization.php
 *
 * Verifies all key framework classes, enums, and interfaces exist
 * and are correctly structured, matching what the example demonstrates.
 */
final class VerifyModernizationTest extends TestCase
{
    public function test_core_classes_exist(): void
    {
        $classes = [
            TelegramBot::class,
            BotFactory::class,
            BotConfig::class,
            EnvLoader::class,
            ApiService::class,
            MessageService::class,
            MediaService::class,
            ChatService::class,
            WebhookService::class,
            PollsService::class,
            InlineService::class,
            TopicsService::class,
            InviteLinksService::class,
            BulkOperationManager::class,
            BulkResult::class,
            CommandHandler::class,
        ];

        foreach ($classes as $class) {
            $this->assertTrue(class_exists($class), "Class {$class} should exist");
        }
    }

    public function test_interface_and_abstract_classes_exist(): void
    {
        $this->assertTrue(interface_exists(HttpClientInterface::class));
        $this->assertTrue(class_exists(CurlHttpClient::class));
        $this->assertTrue(class_exists(StreamHttpClient::class));
        $this->assertTrue(class_exists(HttpClientFactory::class));
    }

    public function test_formatting_classes_exist(): void
    {
        $this->assertTrue(class_exists(MarkdownV2Formatter::class));
        $this->assertTrue(class_exists(HtmlFormatter::class));
    }

    public function test_keyboard_classes_exist(): void
    {
        $this->assertTrue(class_exists(Button::class));
        $this->assertTrue(class_exists(InlineKeyboardBuilder::class));
        $this->assertTrue(class_exists(ReplyKeyboardBuilder::class));
        $this->assertTrue(class_exists(ReplyKeyboardOptions::class));
    }

    public function test_enums_exist_with_correct_values(): void
    {
        $this->assertTrue(enum_exists(ApiMethod::class));
        $this->assertTrue(enum_exists(ParseMode::class));
        $this->assertTrue(enum_exists(ChatAction::class));

        $this->assertSame('sendMessage', ApiMethod::SEND_MESSAGE->value);
        $this->assertSame('sendPhoto', ApiMethod::SEND_PHOTO->value);
        $this->assertSame('getUpdates', ApiMethod::GET_UPDATES->value);
        $this->assertSame('setWebhook', ApiMethod::SET_WEBHOOK->value);
    }

    public function test_exception_hierarchy_exists(): void
    {
        $this->assertTrue(class_exists(TelegramException::class));
        $this->assertTrue(class_exists(ApiException::class));
        $this->assertTrue(class_exists(HttpClientException::class));

        $apiEx = new ApiException('test error', 400, 400, []);
        $this->assertInstanceOf(TelegramException::class, $apiEx);

        $httpEx = new HttpClientException('network error');
        $this->assertInstanceOf(TelegramException::class, $httpEx);
    }

    public function test_logging_classes_exist(): void
    {
        $this->assertTrue(class_exists(LoggerFactory::class));
        $this->assertTrue(class_exists(Logger::class));
        $this->assertTrue(class_exists(NullLogger::class));
    }

    public function test_telegram_bot_is_final(): void
    {
        $reflection = new \ReflectionClass(TelegramBot::class);
        $this->assertTrue($reflection->isFinal(), 'TelegramBot must be final');
    }

    public function test_bot_config_is_final_with_readonly_properties(): void
    {
        $reflection = new \ReflectionClass(BotConfig::class);
        $this->assertTrue($reflection->isFinal(), 'BotConfig must be final');

        $config = new BotConfig(token: 'test_token');
        $this->assertSame('test_token', $config->getToken());
        $this->assertSame('https://api.telegram.org/', $config->getApiUrl());
        $this->assertSame(30, $config->getTimeout());
    }

    public function test_bulk_result_is_readonly_class(): void
    {
        $reflection = new \ReflectionClass(BulkResult::class);
        $this->assertTrue($reflection->isReadOnly(), 'BulkResult must be readonly');
    }

    public function test_bulk_result_implements_countable(): void
    {
        $result = new BulkResult(3, 2, 1, [], []);
        $this->assertInstanceOf(\Countable::class, $result);
        $this->assertCount(3, $result);
    }

    public function test_service_accessors_return_correct_instances(): void
    {
        $mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $bot = new TelegramBot(null, $config, $mockClient);

        $this->assertInstanceOf(MessageService::class, $bot->messages());
        $this->assertInstanceOf(MediaService::class, $bot->media());
        $this->assertInstanceOf(ChatService::class, $bot->chats());
        $this->assertInstanceOf(WebhookService::class, $bot->webhooks());
        $this->assertInstanceOf(PollsService::class, $bot->polls());
        $this->assertInstanceOf(InlineService::class, $bot->inline());
        $this->assertInstanceOf(TopicsService::class, $bot->topics());
        $this->assertInstanceOf(InviteLinksService::class, $bot->inviteLinks());
        $this->assertInstanceOf(CommandHandler::class, $bot->commands());
        $this->assertInstanceOf(ApiService::class, $bot->api());
        $this->assertInstanceOf(MarkdownV2Formatter::class, $bot->formatter());
    }

    public function test_inline_keyboard_builder_can_be_created(): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('Button 1', 'data_1'),
                Button::url('Google', 'https://google.com')
            )
            ->addRow(
                Button::callback('Button 2', 'data_2')
            )
            ->toArray();

        $this->assertArrayHasKey('inline_keyboard', $keyboard);
        $this->assertCount(2, $keyboard['inline_keyboard']);
        $this->assertCount(2, $keyboard['inline_keyboard'][0]);
        $this->assertCount(1, $keyboard['inline_keyboard'][1]);
    }

    public function test_reply_keyboard_builder_can_be_created(): void
    {
        $keyboard = ReplyKeyboardBuilder::create(
            new ReplyKeyboardOptions(resizeKeyboard: true, oneTimeKeyboard: true)
        )
            ->addRow(Button::text('Option 1'), Button::text('Option 2'))
            ->addRow(Button::text('Option 3'))
            ->toArray();

        $this->assertArrayHasKey('keyboard', $keyboard);
        $this->assertArrayHasKey('resize_keyboard', $keyboard);
        $this->assertTrue($keyboard['resize_keyboard']);
        $this->assertCount(2, $keyboard['keyboard']);
    }

    public function test_parse_mode_enum_values(): void
    {
        $this->assertSame('MarkdownV2', ParseMode::MARKDOWN_V2->value);
        $this->assertSame('HTML', ParseMode::HTML->value);
        $this->assertSame('Markdown', ParseMode::MARKDOWN->value);
    }

    public function test_chat_action_enum_has_typing(): void
    {
        $this->assertSame('typing', ChatAction::TYPING->value);
    }
}
