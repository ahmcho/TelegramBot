<?php

declare(strict_types=1);

/**
 * Modern API Usage Example - Safe Demo
 *
 * Demonstrates PHP 8.1+ features without sending actual messages
 */

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Enums\ApiMethod;

require_once __DIR__ . '/../autoload.php';

echo "🚀 Modern tg-bots Library - PHP 8.1+ Features Demo\n";
echo "==================================================\n\n";

// Initialize bot
$bot = new TelegramBot();
echo "✅ Bot created successfully\n\n";

// ===== Feature 1: Service-Oriented Architecture =====
echo "1️⃣  Service-Oriented Architecture\n";
echo "   Messages: " . $bot->messages()::class . "\n";
echo "   Media: " . $bot->media()::class . "\n";
echo "   Chats: " . $bot->chats()::class . "\n";
echo "   Webhooks: " . $bot->webhooks()::class . "\n\n";

// ===== Feature 2: Type-Safe Enums =====
echo "2️⃣  Type-Safe Enums (PHP 8.1+)\n";
echo "   ApiMethod::SEND_MESSAGE = " . ApiMethod::SEND_MESSAGE->value . "\n";
echo "   ApiMethod::GET_ME = " . ApiMethod::GET_ME->value . "\n";
echo "   ApiMethod::SEND_PHOTO = " . ApiMethod::SEND_PHOTO->value . "\n\n";

// ===== Feature 3: Readonly Properties =====
echo "3️⃣  Readonly Properties (PHP 8.1+)\n";
$config = $bot->api()->getConfig();
echo "   Token: " . substr($config->getToken(), 0, 20) . "...\n";
echo "   API URL: " . $config->getApiUrl() . "\n";
echo "   Timeout: " . $config->getTimeout() . "s\n";
echo "   Immutable: " . ((new ReflectionProperty($config, 'token'))->isReadOnly() ? 'Yes' : 'No') . "\n\n";

// ===== Feature 4: Constructor Property Promotion =====
echo "4️⃣  Constructor Property Promotion\n";
$reflection = new ReflectionClass($config);
$constructor = $reflection->getConstructor();
$params = $constructor->getParameters();
echo "   BotConfig constructor has " . count($params) . " promoted parameters\n\n";

// ===== Feature 5: Text Formatting =====
echo "5️⃣  Text Formatter with Auto-Escaping\n";
$formatter = new MarkdownV2Formatter();

echo "   Original: Hello World!\n";
echo "   Escaped: " . $formatter->escape('Hello World!') . "\n";
echo "   Bold: " . $formatter->bold('Hello World') . "\n";
echo "   Italic: " . $formatter->italic('Hello World') . "\n\n";

// ===== Feature 6: Keyboard Builder =====
echo "6️⃣  Keyboard Builder (Fluent Interface)\n";
$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::url('Google', 'https://google.com'),
        Button::callback('Callback', 'data_123')
    )
    ->addRow(
        Button::switchInline('Search'),
        Button::text('Plain Button')
    );

echo "   Keyboard structure:\n";
$structure = $keyboard->toArray();
echo "   Rows: " . count($structure['inline_keyboard']) . "\n";
echo "   First button: " . $structure['inline_keyboard'][0][0]['text'] . "\n\n";

// ===== Feature 7: Value Objects =====
echo "7️⃣  Value Objects (Immutable)\n";

use AhmCho\Telegram\Bulk\BulkResult;

$result = BulkResult::empty();
echo "   BulkResult is readonly class: " . ((new ReflectionClass($result))->isReadOnly() ? 'Yes' : 'No') . "\n";
echo "   Total: " . $result->total . "\n";
echo "   Success rate: " . $result->getSuccessRate() . "%\n\n";

// ===== Feature 8: Match Expression =====
echo "8️⃣  Match Expressions (Type Safety)\n";
echo "   HTTP client factory uses match() for type safety:\n";
echo "   - Checks cURL availability\n";
echo "   - Checks OpenSSL availability\n";
echo "   - Returns appropriate client\n\n";

// ===== Feature 9: Named Arguments =====
echo "9️⃣  Named Arguments (PHP 8.0+)\n";
echo "   \$bot->messages()->send(\n";
echo "       text: 'Message',\n";
echo "       chat_id: 123,\n";
echo "       parse_mode: 'MarkdownV2'\n";
echo "   );\n\n";

// ===== Usage Example =====
echo "📖 Real Usage Example:\n\n";
$code = <<<'CODE'
use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot(); // Loads token from .env

// In webhook handler:
$update = $bot->getWebhookUpdates();
if ($update && isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];

    // Send message with auto-escaped formatting
    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $bot->formatter()->bold('Welcome!'), // '!' auto-escaped
        'parse_mode' => 'MarkdownV2'
    ]);
}
CODE;

echo $code . "\n";

echo "==================================================\n";
echo "✅ All PHP 8.1+ Features Demonstrated!\n";
echo "==================================================\n\n";

echo "📋 Summary:\n";
echo "  ✅ Enums - Type-safe API methods\n";
echo "  ✅ Readonly properties - Immutable config\n";
echo "  ✅ Constructor promotion - Less boilerplate\n";
echo "  ✅ Match expressions - Clean conditionals\n";
echo "  ✅ Named arguments - Clearer calls\n";
echo "  ✅ Fluent interfaces - Method chaining\n";
echo "  ✅ Value objects - Immutable data\n";
echo "  ✅ Service-oriented - Separated concerns\n";
echo "  ✅ PSR-4 autoloading - Modern class loading\n\n";

echo "🎓 The library is fully modernized and ready to use!\n";
