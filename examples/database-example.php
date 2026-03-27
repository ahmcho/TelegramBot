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

require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Database\SqliteUserRepository;
use AhmCho\Telegram\Database\UserFilters;

// Load .env file
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}

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
    $userRepo = new SqliteUserRepository(__DIR__ . '/../data/bot.db');
    $bot->setUserRepository($userRepo);
    echo "Database connected: " . __DIR__ . '/../data/bot.db' . "\n";
} catch (\Exception $e) {
    echo "Database not available: " . $e->getMessage() . "\n";
    echo "Bot will continue without database functionality.\n";
    $userRepo = null;
}

// Command: /start
function handleStart(array $update, TelegramBot $bot, ?SqliteUserRepository $userRepo): void
{
    $chatId = $update['message']['chat']['id'];
    $firstName = $update['message']['from']['first_name'] ?? 'Friend';

    // Save user to database
    if ($userRepo !== null) {
        $bot->saveUserFromUpdate($update);
    }

    $text = "Hello, $firstName! 👋\n\n";
    $text .= "I'm a bot with database support. Try these commands:\n";
    $text .= "/stats - View database statistics\n";
    $text .= "/me - View your stored data\n";
    $text .= "/broadcast_active - Send message to active users (admin only)\n";
    $text .= "/broadcast_premium - Send message to premium users (admin only)";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /stats
function handleStats(array $update, TelegramBot $bot, ?SqliteUserRepository $userRepo): void
{
    $chatId = $update['message']['chat']['id'];

    if ($userRepo === null) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    $stats = $userRepo->getStats();

    $text = "📊 Database Statistics\n\n";
    $text .= "Total Users: {$stats['total']}\n";
    $text .= "Active (30 days): {$stats['active_30_days']}\n";
    $text .= "Premium Users: {$stats['premium']}\n";
    $text .= "With Username: {$stats['with_username']}\n";
    $text .= "Bots: {$stats['bots']}\n";
    $text .= "New Today: {$stats['new_today']}";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /me
function handleMe(array $update, TelegramBot $bot, ?SqliteUserRepository $userRepo): void
{
    $chatId = $update['message']['chat']['id'];
    $telegramId = $update['message']['from']['id'];

    if ($userRepo === null) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    $user = $userRepo->findByTelegramId($telegramId);

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
    $text .= "Language: " . ($user->languageCode ?: 'unknown') . "\n";
    $text .= "Premium: " . ($user->isPremium ? 'Yes ✅' : 'No') . "\n";
    $text .= "Bot: " . ($user->isBot ? 'Yes 🤖' : 'No') . "\n";
    $text .= "Registered: " . $user->createdAt->format('Y-m-d H:i:s') . "\n";
    $text .= "Last Active: " . $user->lastActive->format('Y-m-d H:i:s');

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /broadcast_active
function handleBroadcastActive(array $update, TelegramBot $bot, ?SqliteUserRepository $userRepo): void
{
    $chatId = $update['message']['chat']['id'];

    if ($userRepo === null) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    // Send to users active in last 7 days
    $filters = UserFilters::create()
        ->withActiveSince(date('Y-m-d H:i:s', strtotime('-7 days')));

    $result = $bot->broadcastToDatabase(
        '🔔 Hello! This is a broadcast to active users (last 7 days).',
        ['parse_mode' => 'Markdown'],
        $filters
    );

    $text = "✅ Broadcast completed\n\n";
    $text .= "Total: {$result->total}\n";
    $text .= "Successful: {$result->successful}\n";
    $text .= "Failed: {$result->failed}";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Command: /broadcast_premium
function handleBroadcastPremium(array $update, TelegramBot $bot, ?SqliteUserRepository $userRepo): void
{
    $chatId = $update['message']['chat']['id'];

    if ($userRepo === null) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => 'Database is not configured.'
        ]);
        return;
    }

    // Send only to premium users
    $filters = UserFilters::create()
        ->withIsPremium(true);

    $result = $bot->broadcastToDatabase(
        '⭐ Special message for our premium users! Thank you for your support.',
        [],
        $filters
    );

    $text = "✅ Premium broadcast completed\n\n";
    $text .= "Total: {$result->total}\n";
    $text .= "Successful: {$result->successful}\n";
    $text .= "Failed: {$result->failed}";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $text
    ]);
}

// Message handler
function handleMessage(array $update, TelegramBot $bot, ?SqliteUserRepository $userRepo): void
{
    // Save user to database on any message
    if ($userRepo !== null) {
        $bot->saveUserFromUpdate($update);
    }

    $text = $update['message']['text'] ?? '';
    $chatId = $update['message']['chat']['id'];

    // Handle commands
    if (strpos($text, '/start') === 0) {
        handleStart($update, $bot, $userRepo);
    } elseif (strpos($text, '/stats') === 0) {
        handleStats($update, $bot, $userRepo);
    } elseif (strpos($text, '/me') === 0) {
        handleMe($update, $bot, $userRepo);
    } elseif (strpos($text, '/broadcast_active') === 0) {
        handleBroadcastActive($update, $bot, $userRepo);
    } elseif (strpos($text, '/broadcast_premium') === 0) {
        handleBroadcastPremium($update, $bot, $userRepo);
    } else {
        // Echo non-command messages
        $bot->messages()->send([
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
                handleMessage($update, $bot, $userRepo);
                $offset = $update['update_id'] + 1;
            }
            echo "Processed " . count($updates) . " updates\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        sleep(5);
    }
}
