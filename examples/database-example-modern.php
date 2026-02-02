<?php

declare(strict_types=1);

/**
 * Database Example - Bot with User Storage
 *
 * This example shows how to use the new modernized tg-bots library
 * with database functionality for user storage and bulk messaging.
 *
 * Run this script with long polling or set up a webhook pointing to it.
 */

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Database\UserFilters;
use AhmCho\Telegram\Database\SqliteUserRepository;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;

require_once __DIR__ . '/../autoload.php';

// Initialize bot with database
$bot = new TelegramBot();

try {
    $repository = new SqliteUserRepository(__DIR__ . '/../data/bot.db');
    $bot->setUserRepository($repository);
    echo "Database connected: " . __DIR__ . "/../data/bot.db\n";
} catch (Exception $e) {
    echo "Database not available: " . $e->getMessage() . "\n";
    echo "Bot will continue without database functionality.\n";
    $repository = null;
}

// Command handlers
function handleStart(array $update, TelegramBot $bot): void
{
    $chatId = $update['message']['chat']['id'];
    $firstName = $update['message']['from']['first_name'] ?? 'Friend';

    // Save user to database
    $bot->saveUserFromUpdate($update);

    // Create inline keyboard
    $keyboard = InlineKeyboardBuilder::create()
        ->addRow(
            Button::callback('📊 Statistics', 'stats'),
            Button::callback('👤 My Info', 'me')
        )
        ->addRow(
            Button::callback('📢 Broadcast Active', 'broadcast_active'),
            Button::callback('⭐ Broadcast Premium', 'broadcast_premium')
        );

    $text = "Hello, $firstName! 👋\n\n";
    $text .= "I'm a modern bot with PHP 8.1+ features and database support.\n";
    $text .= "Try the buttons below to explore my capabilities.";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text,
        'reply_markup' => $keyboard->build()
    ]);
}

function handleStats(array $update, TelegramBot $bot): void
{
    $chatId = $update['callback_query']['message']['chat']['id'];

    if (!$bot->api()->getConfig()->shouldThrowExceptions()) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    $repository = new SqliteUserRepository(__DIR__ . '/../data/bot.db');
    $stats = $repository->getStats();

    $text = "📊 Database Statistics\n\n";
    $text .= "Total Users: {$stats['total']}\n";
    $text .= "Active (30 days): {$stats['active_30_days']}\n";
    $text .= "Premium Users: {$stats['premium']}\n";
    $text .= "With Username: {$stats['with_username']}\n";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

function handleMe(array $update, TelegramBot $bot): void
{
    $chatId = $update['callback_query']['message']['chat']['id'];
    $telegramId = $update['callback_query']['from']['id'];

    $repository = new SqliteUserRepository(__DIR__ . '/../data/bot.db');
    $user = $repository->findByTelegramId($telegramId);

    if ($user === null) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'You are not in the database yet. Send /start to register.'
        ]);
        return;
    }

    $text = "👤 Your Information\n\n";
    $text .= "Telegram ID: {$user->telegramId}\n";
    $text .= "Chat ID: {$user->chatId}\n";
    $text .= "Name: {$user->firstName} {$user->lastName}\n";
    $text .= "Username: @" . ($user->username ?: 'none') . "\n";
    $text .= "Premium: " . ($user->isPremium ? 'Yes ✅' : 'No') . "\n";
    $text .= "Registered: {$user->createdAt->format('Y-m-d H:i:s')}\n";
    $text .= "Last Active: {$user->lastActive->format('Y-m-d H:i:s')}";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

function handleBroadcastActive(array $update, TelegramBot $bot): void
{
    $chatId = $update['callback_query']['message']['chat']['id'];

    // Send to users active in last 7 days
    $filters = UserFilters::create()
        ->withActiveSince(date('Y-m-d H:i:s', strtotime('-7 days')));

    $results = $bot->broadcastToDatabase(
        '🔔 Hello! This is a broadcast to active users (last 7 days).',
        [],
        $filters
    );

    $text = "✅ Broadcast completed\n\n";
    $text .= "Total: {$results->total}\n";
    $text .= "Successful: {$results->successful}\n";
    $text .= "Failed: {$results->failed}";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

function handleBroadcastPremium(array $update, TelegramBot $bot): void
{
    $chatId = $update['callback_query']['message']['chat']['id'];

    // Send only to premium users
    $filters = UserFilters::create()->withIsPremium(true);

    $results = $bot->broadcastToDatabase(
        '⭐ Special message for our premium users! Thank you for your support.',
        [],
        $filters
    );

    $text = "✅ Premium broadcast completed\n\n";
    $text .= "Total: {$results->total}\n";
    $text .= "Successful: {$results->successful}\n";
    $text .= "Failed: {$results->failed}";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Message handler
function handleMessage(array $update, TelegramBot $bot): void
{
    // Save user to database on any message
    $bot->saveUserFromUpdate($update);

    $text = $update['message']['text'] ?? '';
    $chatId = $update['message']['chat']['id'];

    // Handle commands
    if (strpos($text, '/start') === 0) {
        handleStart($update, $bot);
    } elseif (strpos($text, '/stats') === 0) {
        handleStats($update, $bot);
    } elseif (strpos($text, '/me') === 0) {
        handleMe($update, $bot);
    } elseif (strpos($text, '/broadcast_active') === 0) {
        handleBroadcastActive($update, $bot);
    } elseif (strpos($text, '/broadcast_premium') === 0) {
        handleBroadcastPremium($update, $bot);
    } else {
        // Echo non-command messages
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => "You said: $text\n\nTry /start to see available commands."
        ]);
    }
}

// Main loop
echo "Modern Bot started with database support...\n";
echo "Press Ctrl+C to stop.\n\n";

$offset = 0;

while (true) {
    try {
        $updates = $bot->getUpdates([
            'offset' => $offset,
            'limit' => 100,
            'timeout' => 30
        ]);

        if (!empty($updates)) {
            foreach ($updates as $update) {
                handleMessage($update, $bot);
                $offset = $update['update_id'] + 1;
            }
            echo "Processed " . count($updates) . " updates\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
