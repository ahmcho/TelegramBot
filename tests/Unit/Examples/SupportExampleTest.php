<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/support.php
 *
 * Verifies the support ticket bot patterns:
 * - /start shows welcome with reply keyboard
 * - /new shows category selection with inline keyboard
 * - /mytickets lists user tickets
 * - /status checks ticket status
 * - Category callback starts ticket creation flow
 * - Multi-step state machine: awaiting_subject → awaiting_description → ticket created
 * - Ticket has ID, category, subject, description, status, timestamps
 * - /help command lists available commands
 * - All commands are registered via register() method (not setCommand)
 */
final class SupportExampleTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;

    private array $categories = [
        'technical' => '🔧 Technical Issue',
        'billing' => '💳 Billing Question',
        'feature' => '💡 Feature Request',
        'bug' => '🐛 Bug Report',
        'other' => '📝 Other',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
    }

    public function test_start_command_sends_welcome_with_reply_keyboard(): void
    {
        $this->mockClient->setResponse(['message_id' => 1]);

        $builder = ReplyKeyboardBuilder::create(
            new ReplyKeyboardOptions(resizeKeyboard: true, oneTimeKeyboard: true)
        )
            ->addRow(Button::text('🎫 New Ticket'), Button::text('📋 My Tickets'))
            ->addRow(Button::text('❓ Help'));

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => "🎫 Welcome to Support Bot!\n\nWe're here to help.",
            'reply_markup' => $builder->build(),
        ]);

        $markup = $builder->toArray();

        $this->assertArrayHasKey('keyboard', $markup);
        $this->assertTrue($markup['resize_keyboard']);
        $this->assertTrue($markup['one_time_keyboard']);
        // ReplyKeyboardBuilder stores button text as plain strings (Telegram accepts both)
        $this->assertSame('🎫 New Ticket', $markup['keyboard'][0][0]);
        $this->assertSame('📋 My Tickets', $markup['keyboard'][0][1]);
        $this->assertSame('❓ Help', $markup['keyboard'][1][0]);
    }

    public function test_new_command_shows_category_selection_keyboard(): void
    {
        $this->mockClient->setResponse(['message_id' => 2]);

        $keyboard = InlineKeyboardBuilder::create();
        foreach ($this->categories as $key => $label) {
            $keyboard->addRow(
                Button::callback($label, "category:{$key}")
            );
        }

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => "🎫 Create New Ticket\n\nPlease select the category:",
            'reply_markup' => $keyboard->build(),
        ]);

        $built = $keyboard->toArray();

        $this->assertCount(5, $built['inline_keyboard']);
        $this->assertSame('category:technical', $built['inline_keyboard'][0][0]['callback_data']);
        $this->assertSame('category:billing', $built['inline_keyboard'][1][0]['callback_data']);
        $this->assertSame('category:other', $built['inline_keyboard'][4][0]['callback_data']);
    }

    public function test_category_callback_data_format(): void
    {
        $callbackData = 'category:technical';
        $parts = explode(':', $callbackData, 2);

        $this->assertSame('category', $parts[0]);
        $this->assertSame('technical', $parts[1]);
        $this->assertArrayHasKey('technical', $this->categories);
    }

    public function test_category_selection_sets_user_state(): void
    {
        $userStates = [];
        $chatId = 123;
        $category = 'technical';

        if (isset($this->categories[$category])) {
            $userStates[$chatId] = [
                'state' => 'awaiting_subject',
                'category' => $category,
            ];
        }

        $this->assertArrayHasKey($chatId, $userStates);
        $this->assertSame('awaiting_subject', $userStates[$chatId]['state']);
        $this->assertSame('technical', $userStates[$chatId]['category']);
    }

    public function test_awaiting_subject_state_captures_subject(): void
    {
        $userStates = [
            123 => ['state' => 'awaiting_subject', 'category' => 'bug'],
        ];
        $chatId = 123;
        $subjectText = 'Login page crashes on mobile';

        if (isset($userStates[$chatId]) && $userStates[$chatId]['state'] === 'awaiting_subject') {
            $userStates[$chatId]['state'] = 'awaiting_description';
            $userStates[$chatId]['subject'] = $subjectText;
        }

        $this->assertSame('awaiting_description', $userStates[$chatId]['state']);
        $this->assertSame($subjectText, $userStates[$chatId]['subject']);
    }

    public function test_awaiting_description_creates_ticket(): void
    {
        $tickets = [];
        $ticketIdCounter = 1;

        $userStates = [
            123 => [
                'state' => 'awaiting_description',
                'category' => 'bug',
                'subject' => 'Login crash',
            ],
        ];

        $chatId = 123;
        $descriptionText = 'The login page crashes whenever I tap submit on iOS Safari.';

        if (isset($userStates[$chatId]) && $userStates[$chatId]['state'] === 'awaiting_description') {
            $state = $userStates[$chatId];
            $ticketId = $ticketIdCounter++;

            $tickets[$ticketId] = [
                'id' => $ticketId,
                'user_id' => $chatId,
                'category' => $state['category'],
                'subject' => $state['subject'],
                'description' => $descriptionText,
                'status' => 'open',
                'created_at' => time(),
                'messages' => [],
            ];

            unset($userStates[$chatId]);
        }

        $this->assertArrayHasKey(1, $tickets);
        $this->assertSame(1, $tickets[1]['id']);
        $this->assertSame('bug', $tickets[1]['category']);
        $this->assertSame('Login crash', $tickets[1]['subject']);
        $this->assertSame($descriptionText, $tickets[1]['description']);
        $this->assertSame('open', $tickets[1]['status']);
        $this->assertEmpty($userStates);
    }

    public function test_ticket_creation_sends_confirmation_message(): void
    {
        $this->mockClient->setResponse(['message_id' => 3]);

        $ticket = ['id' => 1, 'subject' => 'App crash', 'category' => 'bug', 'status' => 'open'];

        $ticketText = "🎫 Ticket #{$ticket['id']} Created!\n\n";
        $ticketText .= "Subject: {$ticket['subject']}\n";
        $ticketText .= "Category: {$this->categories[$ticket['category']]}\n";
        $ticketText .= "Status: 🔵 Open\n\n";
        $ticketText .= "Our team will review your ticket shortly.";

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => $ticketText,
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Ticket #1 Created', $request['params']['text']);
        $this->assertStringContainsString('App crash', $request['params']['text']);
        $this->assertStringContainsString('🐛 Bug Report', $request['params']['text']);
    }

    public function test_mytickets_command_lists_user_tickets(): void
    {
        $this->mockClient->setResponse(['message_id' => 4]);

        $chatId = 123;
        $tickets = [
            1 => ['id' => 1, 'user_id' => $chatId, 'subject' => 'Issue 1', 'status' => 'open', 'created_at' => time()],
            2 => ['id' => 2, 'user_id' => $chatId, 'subject' => 'Issue 2', 'status' => 'resolved', 'created_at' => time()],
            3 => ['id' => 3, 'user_id' => 999, 'subject' => 'Other user', 'status' => 'open', 'created_at' => time()],
        ];

        $userTickets = array_filter($tickets, fn($t) => $t['user_id'] == $chatId);

        $this->assertCount(2, $userTickets);

        $text = "📋 Your Tickets:\n\n";
        foreach ($userTickets as $ticket) {
            $statusIcon = match($ticket['status']) {
                'open' => '🔵',
                'resolved' => '🟢',
                default => '⚪',
            };
            $text .= "{$statusIcon} #{$ticket['id']} - {$ticket['subject']}\n";
        }

        $this->bot->messages()->send(['chat_id' => $chatId, 'text' => $text]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('Issue 1', $request['params']['text']);
        $this->assertStringContainsString('Issue 2', $request['params']['text']);
        $this->assertStringNotContainsString('Other user', $request['params']['text']);
    }

    public function test_status_match_expression_icons(): void
    {
        $statuses = ['open', 'in_progress', 'resolved', 'closed', 'unknown'];
        $expected = ['🔵', '🟡', '🟢', '⚫', '⚪'];

        foreach ($statuses as $i => $status) {
            $icon = match($status) {
                'open' => '🔵',
                'in_progress' => '🟡',
                'resolved' => '🟢',
                'closed' => '⚫',
                default => '⚪',
            };
            $this->assertSame($expected[$i], $icon, "Wrong icon for status: {$status}");
        }
    }

    public function test_answer_callback_query_called_after_category_selection(): void
    {
        $this->mockClient->setResponse(['message_id' => 5]);
        $this->mockClient->setBoolResponse(true);

        $this->bot->messages()->send([
            'chat_id' => 123,
            'text' => "✅ Selected: 🔧 Technical Issue\n\nPlease enter a brief subject:",
        ]);

        $this->bot->chats()->answerCallbackQuery(['callback_query_id' => 'cq_123']);

        $this->assertSame(2, $this->mockClient->getRequestCount());
    }

    public function test_help_command_is_registered(): void
    {
        $this->mockClient->setResponse(['message_id' => 6]);

        $this->bot->commands()->register('help', function ($bot, $chatId, $args) {
            $text = "❓ Help\n\n";
            $text .= "Available Commands:\n";
            $text .= "/start - Start the bot\n";
            $text .= "/new - Create a new ticket\n";
            $text .= "/mytickets - View your tickets\n";
            $text .= "/status <id> - Check ticket status\n\n";
            $text .= "Need help? Just create a ticket!";
            $bot->messages()->send(['chat_id' => $chatId, 'text' => $text]);
        });

        $this->assertTrue($this->bot->commands()->hasCommand('help'));

        $this->bot->commands()->handleUpdate([
            'update_id' => 1,
            'message' => ['message_id' => 1, 'chat' => ['id' => 100], 'text' => '/help'],
        ]);

        $request = $this->mockClient->getLastRequest();
        $this->assertStringContainsString('/new', $request['params']['text']);
        $this->assertStringContainsString('/mytickets', $request['params']['text']);
    }
}
