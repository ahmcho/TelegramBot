<?php

declare(strict_types=1);

require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;

/**
 * Support Ticket Bot Example
 *
 * Demonstrates a customer support ticket system with:
 * - Ticket creation
 * - Issue categorization
 * - Status tracking
 * - Admin notifications
 */

// Ticket storage (in production, use a database)
$tickets = [];
$ticketIdCounter = 1;

// Admin chat ID (replace with actual admin ID)
define('ADMIN_CHAT_ID', getenv('ADMIN_CHAT_ID') ?: '0');

$bot = new TelegramBot();

// Issue categories
$categories = [
    'technical' => '🔧 Technical Issue',
    'billing' => '💳 Billing Question',
    'feature' => '💡 Feature Request',
    'bug' => '🐛 Bug Report',
    'other' => '📝 Other'
];

// Register commands
$bot->commands()
    ->register('start', function ($bot, $chatId, $args) {
        $welcome = "🎫 Welcome to Support Bot!\n\n";
        $welcome .= "We're here to help. Create a ticket and our team will assist you.\n\n";
        $welcome .= "Commands:\n";
        $welcome .= "/new - Create a new ticket\n";
        $welcome .= "/mytickets - View your tickets\n";
        $welcome .= "/status <id> - Check ticket status\n";

        $keyboard = ReplyKeyboardBuilder::create(
                new ReplyKeyboardOptions(resizeKeyboard: true, oneTimeKeyboard: true)
            )
            ->addRow(Button::text('🎫 New Ticket'), Button::text('📋 My Tickets'))
            ->addRow(Button::text('❓ Help'))
            ->build();

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $welcome,
            'reply_markup' => $keyboard
        ]);
    }, 'Start the support bot')

    ->register('new', function ($bot, $chatId, $args) use ($categories) {
        // Show category selection
        $text = "🎫 Create New Ticket\n\n";
        $text .= "Please select the category that best describes your issue:";

        $keyboard = InlineKeyboardBuilder::create();
        foreach ($categories as $key => $label) {
            $keyboard->addRow(
                Button::callback($label, "category:{$key}")
            );
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text,
            'reply_markup' => $keyboard->build()
        ]);
    }, 'Create a new ticket')

    ->register('mytickets', function ($bot, $chatId, $args) use (&$tickets) {
        $userTickets = array_filter($tickets, fn($t) => $t['user_id'] == $chatId);

        if (empty($userTickets)) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => 'You have no tickets yet.\n\nUse /new to create one.'
            ]);
            return;
        }

        $text = "📋 Your Tickets:\n\n";

        foreach ($userTickets as $ticket) {
            $statusIcon = match($ticket['status']) {
                'open' => '🔵',
                'in_progress' => '🟡',
                'resolved' => '🟢',
                'closed' => '⚫',
                default => '⚪'
            };

            $text .= "{$statusIcon} #{$ticket['id']} - {$ticket['subject']}\n";
            $text .= "   Status: " . ucfirst(str_replace('_', ' ', $ticket['status'])) . "\n";
            $text .= "   Created: " . date('Y-m-d H:i', $ticket['created_at']) . "\n\n";
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }, 'View your tickets')

    ->register('status', function ($bot, $chatId, $args) use (&$tickets) {
        if (empty($args)) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => 'Please provide a ticket ID.\n\nUsage: /status <ticket_id>'
            ]);
            return;
        }

        $ticketId = intval($args[0]);
        $ticket = $tickets[$ticketId] ?? null;

        if (!$ticket || $ticket['user_id'] != $chatId) {
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "Ticket #{$ticketId} not found."
            ]);
            return;
        }

        $statusIcon = match($ticket['status']) {
            'open' => '🔵',
            'in_progress' => '🟡',
            'resolved' => '🟢',
            'closed' => '⚫',
            default => '⚪'
        };

        $text = "🎫 Ticket #{$ticket['id']}\n\n";
        $text .= "Subject: {$ticket['subject']}\n";
        $text .= "Category: {$ticket['category']}\n";
        $text .= "Status: {$statusIcon} " . ucfirst(str_replace('_', ' ', $ticket['status'])) . "\n";
        $text .= "Created: " . date('Y-m-d H:i', $ticket['created_at']) . "\n\n";
        $text .= "Description:\n{$ticket['description']}";

        if (!empty($ticket['messages'])) {
            $text .= "\n\n💬 Messages:\n";
            foreach ($ticket['messages'] as $msg) {
                $sender = $msg['from_admin'] ? 'Admin' : 'You';
                $text .= "{$sender}: {$msg['text']}\n";
            }
        }

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    }, 'Check ticket status')

    ->register('help', function ($bot, $chatId, $args) {
        $text = "❓ Help\n\n";
        $text .= "Available Commands:\n";
        $text .= "/start - Start the bot\n";
        $text .= "/new - Create a new ticket\n";
        $text .= "/mytickets - View your tickets\n";
        $text .= "/status <id> - Check ticket status\n\n";
        $text .= "Need help? Just create a ticket!";

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => $text
        ]);
    });

// User state for ticket creation (temporary, in production use session/storage)
$userStates = [];

echo "Support bot started. Polling for updates...\n";

$offset = 0;

while (true) {
    try {
        $updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 30]);

        foreach ($updates as $update) {
            $offset = $update['update_id'] + 1;

            // Handle callback queries
            if (isset($update['callback_query'])) {
                $query = $update['callback_query'];
                $chatId = $query['message']['chat']['id'];
                $data = $query['data'];

                // Category selection
                if (str_starts_with($data, 'category:')) {
                    $category = substr($data, 9);

                    if (isset($categories[$category])) {
                        $userStates[$chatId] = [
                            'state' => 'awaiting_subject',
                            'category' => $category
                        ];

                        $bot->messages()->send([
                            'chat_id' => $chatId,
                            'text' => "✅ Selected: {$categories[$category]}\n\nPlease enter a brief subject for your ticket:"
                        ]);

                        $bot->chats()->answerCallbackQuery(['callback_query_id' => $query['id']]);
                    }
                }
            }

            // Handle messages
            elseif (isset($update['message'])) {
                $chatId = $update['message']['chat']['id'];
                $text = $update['message']['text'] ?? '';

                // Check if user is in ticket creation flow
                if (isset($userStates[$chatId])) {
                    $state = $userStates[$chatId];

                    if ($state['state'] === 'awaiting_subject') {
                        $userStates[$chatId]['state'] = 'awaiting_description';
                        $userStates[$chatId]['subject'] = $text;

                        $bot->messages()->send([
                            'chat_id' => $chatId,
                            'text' => '✅ Subject saved.\n\nPlease describe your issue in detail:'
                        ]);
                    }
                    elseif ($state['state'] === 'awaiting_description') {
                        // Create the ticket
                        global $tickets, $ticketIdCounter;
                        $ticketId = $ticketIdCounter++;

                        $tickets[$ticketId] = [
                            'id' => $ticketId,
                            'user_id' => $chatId,
                            'category' => $state['category'],
                            'subject' => $state['subject'],
                            'description' => $text,
                            'status' => 'open',
                            'created_at' => time(),
                            'messages' => []
                        ];

                        unset($userStates[$chatId]);

                        $ticketText = "🎫 Ticket #{$ticketId} Created!\n\n";
                        $ticketText .= "Subject: {$state['subject']}\n";
                        $ticketText .= "Category: {$categories[$state['category']]}\n";
                        $ticketText .= "Status: 🔵 Open\n\n";
                        $ticketText .= "Our team will review your ticket shortly.";

                        $bot->messages()->send([
                            'chat_id' => $chatId,
                            'text' => $ticketText
                        ]);

                        // Notify admin (if configured)
                        if (ADMIN_CHAT_ID != '0') {
                            $adminText = "🔔 New Ticket #{$ticketId}\n\n";
                            $adminText .= "From: {$chatId}\n";
                            $adminText .= "Subject: {$state['subject']}\n";
                            $adminText .= "Category: {$categories[$state['category']]}\n\n";
                            $adminText .= $text;

                            $bot->messages()->send([
                                'chat_id' => ADMIN_CHAT_ID,
                                'text' => $adminText
                            ]);
                        }
                    }
                }
                // Handle commands normally
                else {
                    $bot->commands()->handleUpdate($update);
                }
            }
        }
    } catch (\Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        sleep(5);
    }
}
