<?php

/**
 * Database Example - Bot with User Storage
 *
 * This example shows how to use the database functionality to:
 * 1. Automatically save users when they interact with the bot
 * 2. Query user data
 * 3. Send targeted messages based on user properties
 *
 * Run this script with long polling or set up a webhook pointing to it.
 */

// Load .env file if it exists
require_once __DIR__ . '/../src/dotenv.php';

require_once __DIR__ . '/../src/TelegramBot.php';
require_once __DIR__ . '/../src/Database.php';

// Check for bot token
if (!getenv('TELEGRAM_BOT_TOKEN') && empty($_ENV['TELEGRAM_BOT_TOKEN'])) {
    die("Error: TELEGRAM_BOT_TOKEN environment variable not set.\n" .
        "Please create a .env file with: TELEGRAM_BOT_TOKEN=your_token_here\n" .
        "Or set it with: export TELEGRAM_BOT_TOKEN='your_token_here'\n");
}

// Initialize bot
$bot = new TelegramBot();

// Initialize database (optional - bot works without it)
try {
    $database = new Database(__DIR__ . '/../data/bot.db');
    $bot->setDatabase($database);
    echo "Database connected: " . $database->getDbPath() . "\n";
} catch (Exception $e) {
    echo "Database not available: " . $e->getMessage() . "\n";
    echo "Bot will continue without database functionality.\n";
    $database = null;
}

// Command: /start
function handleStart(array $update, TelegramBot $bot, ?Database $database): void
{
    $chatId = $update['message']['chat']['id'];
    $firstName = $update['message']['from']['first_name'] ?? 'Friend';

    // Save user to database
    if ($database !== null) {
        $bot->saveUserFromUpdate($update);
    }

    $text = "Hello, $firstName! 👋\n\n";
    $text .= "I'm a bot with database support. Try these commands:\n";
    $text .= "/stats - View database statistics\n";
    $text .= "/me - View your stored data\n";
    $text .= "/broadcast_active - Send message to active users (admin only)\n";
    $text .= "/broadcast_premium - Send message to premium users (admin only)";

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /stats
function handleStats(array $update, TelegramBot $bot, ?Database $database): void
{
    $chatId = $update['message']['chat']['id'];

    if ($database === null) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    $stats = $database->getStats();

    $text = "📊 Database Statistics\n\n";
    $text .= "Total Users: {$stats['total']}\n";
    $text .= "Active (30 days): {$stats['active_30_days']}\n";
    $text .= "Premium Users: {$stats['premium']}\n";
    $text .= "With Username: {$stats['with_username']}\n";
    $text .= "Bots: {$stats['bots']}\n";
    $text .= "New Today: {$stats['new_today']}";

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /me
function handleMe(array $update, TelegramBot $bot, ?Database $database): void
{
    $chatId = $update['message']['chat']['id'];
    $telegramId = $update['message']['from']['id'];

    if ($database === null) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    $user = $database->getUserByTelegramId($telegramId);

    if ($user === null) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'You are not in the database yet. Send /start to register.'
        ]);
        return;
    }

    $text = "👤 Your Information\n\n";
    $text .= "Telegram ID: {$user['telegram_id']}\n";
    $text .= "Chat ID: {$user['chat_id']}\n";
    $text .= "Name: {$user['first_name']} {$user['last_name']}\n";
    $text .= "Username: @" . ($user['username'] ?: 'none') . "\n";
    $text .= "Language: " . ($user['language_code'] ?: 'unknown') . "\n";
    $text .= "Premium: " . ($user['is_premium'] ? 'Yes ✅' : 'No') . "\n";
    $text .= "Bot: " . ($user['is_bot'] ? 'Yes 🤖' : 'No') . "\n";
    $text .= "Registered: {$user['created_at']}\n";
    $text .= "Last Active: {$user['last_active']}";

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /broadcast_active
function handleBroadcastActive(array $update, TelegramBot $bot, ?Database $database): void
{
    $chatId = $update['message']['chat']['id'];

    if ($database === null) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    // Send to users active in last 7 days
    $results = $bot->broadcastToDatabase(
        '🔔 Hello! This is a broadcast to active users (last 7 days).',
        ['parse_mode' => 'Markdown'],
        ['active_since' => date('Y-m-d H:i:s', strtotime('-7 days'))]
    );

    $text = "✅ Broadcast completed\n\n";
    $text .= "Total: {$results['total']}\n";
    $text .= "Successful: {$results['successful']}\n";
    $text .= "Failed: {$results['failed']}";

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /broadcast_premium
function handleBroadcastPremium(array $update, TelegramBot $bot, ?Database $database): void
{
    $chatId = $update['message']['chat']['id'];

    if ($database === null) {
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    // Send only to premium users
    $results = $bot->broadcastToDatabase(
        '⭐ Special message for our premium users! Thank you for your support.',
        [],
        ['is_premium' => true]
    );

    $text = "✅ Premium broadcast completed\n\n";
    $text .= "Total: {$results['total']}\n";
    $text .= "Successful: {$results['successful']}\n";
    $text .= "Failed: {$results['failed']}";

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Message handler
function handleMessage(array $update, TelegramBot $bot, ?Database $database): void
{
    // Save user to database on any message
    if ($database !== null) {
        $bot->saveUserFromUpdate($update);
    }

    $text = $update['message']['text'] ?? '';
    $chatId = $update['message']['chat']['id'];

    // Handle commands
    if (strpos($text, '/start') === 0) {
        handleStart($update, $bot, $database);
    } elseif (strpos($text, '/stats') === 0) {
        handleStats($update, $bot, $database);
    } elseif (strpos($text, '/me') === 0) {
        handleMe($update, $bot, $database);
    } elseif (strpos($text, '/broadcast_active') === 0) {
        handleBroadcastActive($update, $bot, $database);
    } elseif (strpos($text, '/broadcast_premium') === 0) {
        handleBroadcastPremium($update, $bot, $database);
    } else {
        // Echo non-command messages
        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "You said: $text\n\nTry /start to see available commands."
        ]);
    }
}

// Main loop
echo "Bot started with database support...\n";
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
                handleMessage($update, $bot, $database);
                $offset = $update['update_id'] + 1;
            }
            echo "Processed " . count($updates) . " updates\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
