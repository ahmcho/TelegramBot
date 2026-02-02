<?php

declare(strict_types=1);

/**
 * Modern API Usage Example
 *
 * Demonstrates the new PHP 8.1+ modernized tg-bots library
 */

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;

require_once __DIR__ . '/../autoload.php';

// Old way (still works)
// require_once 'src/TelegramBot.php';
// $bot = new TelegramBot();
// $bot->sendMessage(['chat_id' => 123, 'text' => 'Hello']);

// New way (with PHP 8.1+ features)
$bot = new TelegramBot();

// Example 1: Basic message with formatter
$bot->messages()->send([
    'chat_id' => 162592443,
    'text' => $bot->formatter()->bold('Hello World'),
    'parse_mode' => 'MarkdownV2'
]);

// Example 2: Using named arguments (PHP 8.0+)
$bot->messages()->send([
    'text' => 'Named parameters are clearer',
    'chat_id' => 162592443,
    'parse_mode' => 'MarkdownV2'
]);

// Example 3: Building keyboard with fluent interface
$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::url('Visit Google', 'https://google.com'),
        Button::callback('Click Me', 'callback_data_123')
    )
    ->addRow(
        Button::switchInline('Search', ''),
        Button::switchInlineCurrent('Search Here', 'query')
    );

$bot->messages()->send([
    'chat_id' => 162592443,
    'text' => 'Choose an option:',
    'reply_markup' => $keyboard->build()
]);

// Example 4: Service-oriented API
// Instead of: $bot->sendPhoto(...)
// Use: $bot->media()->sendPhoto(...)

$bot->media()->sendPhoto([
    'chat_id' => 162592443,
    'photo' => 'https://ahmcho.com/storage/app/media/fe2c52a4-87ba-4b36-8121-10d229883b5b.webp',
    'caption' => 'Check this out!'
]);

// Example 5: Database integration with value objects
use AhmCho\Telegram\Database\UserFilters;
use AhmCho\Telegram\Database\SqliteUserRepository;

$repository = new SqliteUserRepository(__DIR__ . '/../data/bot.db');
$bot->setUserRepository($repository);

// Save user from update
$update = $bot->getWebhookUpdates();
if ($update) {
    $bot->saveUserFromUpdate($update);
}

// Broadcast with filter builder
$filters = UserFilters::create()
    ->withIsPremium(true)
    ->withActiveSince(date('Y-m-d H:i:s', strtotime('-30 days')));

$bot->broadcastToDatabase(
    text: 'Special for premium users!',
    commonParams: ['parse_mode' => 'MarkdownV2'],
    filters: $filters
);

echo "Modern API examples executed successfully!\n";
echo "Key PHP 8.1+ features demonstrated:\n";
echo "- Enums for type safety\n";
echo "- Readonly classes and properties\n";
echo "- Constructor property promotion\n";
echo "- Match expressions\n";
echo "- Named arguments\n";
echo "- First-class callables\n";
echo "- Null coalescing assignment\n";
echo "- Service-oriented architecture\n";
