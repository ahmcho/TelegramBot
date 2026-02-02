<?php

declare(strict_types=1);

/**
 * Simple Working Demo
 *
 * Demonstrates the modernized library without sending actual messages
 */

require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Database\UserFilters;

echo "🎯 Modernized tg-bots Library Demo\n";
echo "===================================\n\n";

// Create bot instance (auto-loads token from .env)
$bot = new TelegramBot();
echo "✅ Bot created successfully\n\n";

// Demonstrate service accessors
echo "📊 Service Accessors:\n";
echo "  - Messages: " . ($bot->messages()::class ?? 'OK') . "\n";
echo "  - Media: " . ($bot->media()::class ?? 'OK') . "\n";
echo "  - Chats: " . ($bot->chats()::class ?? 'OK') . "\n";
echo "  - Webhooks: " . ($bot->webhooks()::class ?? 'OK') . "\n";
echo "  - Formatter: " . ($bot->formatter()::class ?? 'OK') . "\n";
echo "  - API: " . ($bot->api()::class ?? 'OK') . "\n\n";

// Demonstrate enums
echo "🔤 Type-Safe Enums:\n";
echo "  - ApiMethod::SEND_MESSAGE = " . ApiMethod::SEND_MESSAGE->value . "\n";
echo "  - ApiMethod::GET_ME = " . ApiMethod::GET_ME->value . "\n";
echo "  - ApiMethod::SEND_PHOTO = " . ApiMethod::SEND_PHOTO->value . "\n\n";

// Demonstrate formatter
echo "✍️  Text Formatter:\n";
$formatter = $bot->formatter();
echo "  - Escape: " . $formatter->escape('test') . "\n";
echo "  - Bold: " . $formatter->bold('Bold Text') . "\n";
echo "  - Italic: " . $formatter->italic('Italic') . "\n\n";

// Demonstrate keyboard builder
echo "⌨️  Keyboard Builder:\n";
$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::url('Google', 'https://google.com'),
        Button::callback('Click', 'callback_123')
    );

echo "  - Keyboard built: " . json_encode($keyboard->toArray(), JSON_PRETTY_PRINT) . "\n\n";

// Demonstrate database filters
echo "🗄️  Database Filters (Fluent Interface):\n";
$filters = UserFilters::create()
    ->withIsPremium(true)
    ->withActiveSince(date('Y-m-d H:i:s', strtotime('-7 days')));

echo "  - Premium filter: " . ($filters->isPremium ? 'true' : 'false') . "\n";
echo "  - Active since: " . $filters->activeSince . "\n\n";

// Demonstrate configuration
echo "⚙️  Bot Configuration:\n";
$config = $bot->api()->getConfig();
echo "  - Token: " . substr($config->getToken(), 0, 20) . "...\n";
echo "  - API URL: " . $config->getApiUrl() . "\n";
echo "  - Timeout: " . $config->getTimeout() . "s\n";
echo "  - Throw exceptions: " . ($config->shouldThrowExceptions() ? 'Yes' : 'No') . "\n\n";

// Demonstrate bulk result
use AhmCho\Telegram\Bulk\BulkResult;

$result = BulkResult::empty();
echo "📦 Bulk Result Value Object:\n";
echo "  - Total: " . $result->total . "\n";
echo "  - Successful: " . $result->successful . "\n";
echo "  - Failed: " . $result->failed . "\n";
echo "  - Is success: " . ($result->isSuccess() ? 'Yes' : 'No') . "\n\n";

echo "===================================\n";
echo "✅ All Features Working!\n";
echo "===================================\n\n";

echo "📋 Summary:\n";
echo "  ✅ Namespaced classes (TGBot\\*)\n";
echo "  ✅ PSR-4 autoloading\n";
echo "  ✅ Service-oriented architecture\n";
echo "  ✅ Type-safe enums\n";
echo "  ✅ Readonly properties\n";
echo "  ✅ Fluent interfaces\n";
echo "  ✅ Value objects\n";
echo "  ✅ SOLID principles\n\n";

echo "🚀 Ready to use! Send /start to your bot first,\n";
echo "   then the bot will be able to send messages back.\n\n";

// Example of how to send when user starts the bot
echo "💡 Example: When user sends /start\n\n";

$code = <<<'PHP'
use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

// In your webhook handler
$update = $bot->getWebhookUpdates();
if ($update && isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];

    // Now you can send messages!
    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => 'Hello from the modernized bot!'
    ]);
}
PHP;

echo $code . "\n";
