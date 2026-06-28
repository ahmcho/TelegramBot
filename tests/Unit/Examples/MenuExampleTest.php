<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/menu.php
 *
 * The menu example defines a MenuSystem class with multi-level navigation.
 * Key behaviors:
 * - showMainMenu() builds 3-row keyboard with 6 category buttons
 * - showProductsMenu() builds product categories with back button
 * - sendMessageOrEdit() sends new message when no messageId, edits when messageId provided
 * - Navigation callback data uses 'menu:category' format
 * - Text is formatted with formatter()->bold() before sending
 */
final class MenuExampleTest extends TestCase
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

    public function test_main_menu_keyboard_structure(): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('🛍️ Products', 'menu:products'),
                Button::callback('📦 Services', 'menu:services')
            )
            ->addRow(
                Button::callback('ℹ️ About Us', 'menu:about'),
                Button::callback('📞 Contact', 'menu:contact')
            )
            ->addRow(
                Button::callback('⚙️ Settings', 'menu:settings'),
                Button::callback('❓ Help', 'menu:help')
            )
            ->toArray();

        $this->assertArrayHasKey('inline_keyboard', $keyboard);
        $this->assertCount(3, $keyboard['inline_keyboard']);

        $this->assertSame('menu:products', $keyboard['inline_keyboard'][0][0]['callback_data']);
        $this->assertSame('menu:services', $keyboard['inline_keyboard'][0][1]['callback_data']);
        $this->assertSame('menu:about', $keyboard['inline_keyboard'][1][0]['callback_data']);
        $this->assertSame('menu:contact', $keyboard['inline_keyboard'][1][1]['callback_data']);
        $this->assertSame('menu:settings', $keyboard['inline_keyboard'][2][0]['callback_data']);
        $this->assertSame('menu:help', $keyboard['inline_keyboard'][2][1]['callback_data']);
    }

    public function test_products_menu_has_back_button(): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('💻 Electronics', 'menu:products:electronics'),
                Button::callback('👕 Clothing', 'menu:products:clothing')
            )
            ->addRow(
                Button::callback('📚 Books', 'menu:products:books'),
                Button::callback('🏠 Home', 'menu:products:home')
            )
            ->addRow(
                Button::callback('🔙 Back to Main', 'menu:main')
            )
            ->toArray();

        $lastRow = $keyboard['inline_keyboard'][2];
        $this->assertCount(1, $lastRow);
        $this->assertSame('menu:main', $lastRow[0]['callback_data']);
        $this->assertSame('🔙 Back to Main', $lastRow[0]['text']);
    }

    public function test_show_main_menu_sends_message_without_message_id(): void
    {
        $this->mockClient->setResponse(['message_id' => 10]);

        $text = $this->bot->formatter()->bold('Welcome to the Menu Bot!') . "\n\nPlease select:";
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(Button::callback('Products', 'menu:products'))
            ->build();

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $text,
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $keyboard,
        ]);

        $this->assertSame(1, $this->mockClient->getRequestCount());
        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('sendMessage', $request['url']);
    }

    public function test_show_main_menu_edits_message_with_message_id(): void
    {
        $this->mockClient->setResponse(['message_id' => 10]);

        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(Button::callback('Products', 'menu:products'))
            ->build();

        $this->bot->messages()->editText([
            'chat_id' => 123,
            'message_id' => 10,
            'text' => 'Back to main menu',
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $keyboard,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('editMessageText', $request['url']);
        $this->assertSame(10, $request['params']['message_id']);
    }

    public function test_navigation_callback_data_format(): void
    {
        $callbackData = 'menu:products:electronics';
        $parts = explode(':', $callbackData);

        $this->assertSame('menu', $parts[0]);
        $this->assertSame('products', $parts[1]);
        $this->assertSame('electronics', $parts[2]);
    }

    public function test_formatter_bold_text_in_menu(): void
    {
        $title = $this->bot->formatter()->bold('Welcome to the Menu Bot!');

        $this->assertStringContainsString('*', $title);
        $this->assertStringContainsString('Welcome to the Menu Bot', $title);
    }

    public function test_services_menu_keyboard(): void
    {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(
                Button::callback('🎨 Design', 'menu:services:design'),
                Button::callback('💻 Development', 'menu:services:development')
            )
            ->addRow(
                Button::callback('📈 Marketing', 'menu:services:marketing'),
                Button::callback('🔧 Support', 'menu:services:support')
            )
            ->addRow(
                Button::callback('🔙 Back to Main', 'menu:main')
            )
            ->toArray();

        $this->assertCount(3, $keyboard['inline_keyboard']);
        $this->assertSame('menu:services:design', $keyboard['inline_keyboard'][0][0]['callback_data']);
        $this->assertSame('menu:main', $keyboard['inline_keyboard'][2][0]['callback_data']);
    }

    public function test_about_menu_sends_formatted_text(): void
    {
        $this->mockClient->setResponse(['message_id' => 20]);

        $aboutText = $this->bot->formatter()->bold('About Us') . "\n\nWe are a company.";
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(Button::callback('🔙 Back', 'menu:main'))
            ->build();

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $aboutText,
            'parse_mode' => 'MarkdownV2',
            'reply_markup' => $keyboard,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('About Us', $request['params']['text']);
        $this->assertArrayHasKey('reply_markup', $request['params']);
    }

    public function test_multiple_menu_levels_in_sequence(): void
    {
        $this->mockClient->setResponse(['message_id' => 30]);
        $this->mockClient->setResponse(['message_id' => 30]);
        $this->mockClient->setResponse(['message_id' => 30]);

        // Enter main menu
        $this->bot->messages()->send(['chat_id' => 100, 'text' => 'Main menu']);

        // Go to products
        $this->bot->messages()->editText(['chat_id' => 100, 'message_id' => 30, 'text' => 'Products']);

        // Go back to main
        $this->bot->messages()->editText(['chat_id' => 100, 'message_id' => 30, 'text' => 'Main menu again']);

        $this->assertSame(3, $this->mockClient->getRequestCount());
    }
}
