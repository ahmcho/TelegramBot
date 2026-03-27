<?php

declare(strict_types=1);

/**
 * Commands Bot Example - Modern API
 *
 * A bot with command handlers (/start, /help)
 * and inline keyboard buttons with callback query handling.
 *
 * Modern features showcased:
 * - Service-oriented API ($bot->messages(), $bot->formatter())
 * - Auto-escaping for MarkdownV2 with special characters
 * - PHP 8.1+ features (strict types, proper typing)
 */

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Enums\ApiMethod;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;

require_once __DIR__ . '/../autoload.php';

// Load environment variables
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

// Command handlers
function handleStart(TelegramBot $bot, int $chatId, string $firstName = ''): void
{
    $greeting = empty($firstName)
        ? 'Hello! Welcome to the Commands Bot.'
        : "Hello $firstName! Welcome to the Commands Bot.";

    $keyboard = InlineKeyboardBuilder::create()
        ->addRow(
            Button::url('📚 Documentation', 'https://core.telegram.org/bots/api'),
            Button::callback('ℹ️ Help', 'cmd_help')
        )
        ->addRow(
            Button::callback('🎮 Features', 'cmd_features'),
            Button::callback('📞 Contact', 'cmd_contact')
        )
        ->toArray();

    // Auto-escaped!
    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $greeting . "\n\nChoose an option below:",
        'parse_mode' => 'MarkdownV2',
        'reply_markup' => $keyboard
    ]);
}

function handleHelp(TelegramBot $bot, int $chatId): void
{
    // Using formatter - auto-escaped!
    $helpText = $bot->formatter()
        ->bold('📖 Available Commands')
        . "\n\n"
        . "/start - Start the bot\n"
        . "/help - Show this help message\n"
        . "/keyboard - Show a custom keyboard\n"
        . "/photo - Send a sample photo\n"
        . "/dice - Roll a dice";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $helpText,
        'parse_mode' => 'MarkdownV2'
    ]);
}

function handleKeyboard(TelegramBot $bot, int $chatId): void
{
    $options = new ReplyKeyboardOptions(
        resizeKeyboard: true,
        oneTimeKeyboard: false
    );

    $keyboard = ReplyKeyboardBuilder::create($options)
        ->addRow(
            Button::text('👍 Like'),
            Button::text('👎 Dislike')
        )
        ->addRow(
            Button::text('❓ Help'),
            Button::text('🎲 Random')
        )
        ->toArray();

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => 'Here is a custom keyboard. Try the buttons!',
        'reply_markup' => $keyboard
    ]);
}

function handlePhoto(TelegramBot $bot, int $chatId): void
{
    // Send a photo from URL - caption auto-escaped!
    $bot->media()->sendPhoto([
        'chat_id' => $chatId,
        'photo' => 'https://picsum.photos/800/600',
        'caption' => '📷 Here is a random photo from Lorem Picsum!',
        'show_caption_above_media' => true,
        'parse_mode' => 'MarkdownV2'
    ]);
}

function handleDice(TelegramBot $bot, int $chatId): void
{
    $bot->media()->sendDice([
        'chat_id' => $chatId
    ]);
}

function handleFeatures(TelegramBot $bot, int $chatId): void
{
    $features = $bot->formatter()
        ->bold('🎮 Bot Features')
        . "\n\n"
        . "✅ Commands \\(/start, /help, /keyboard, /photo, /dice\\)\n"
        . "✅ Inline keyboards\n"
        . "✅ Callback queries\n"
        . "✅ Custom reply keyboards\n"
        . "✅ Media sending \\(photos, videos, etc.\\)\n"
        . "✅ Message formatting \\(Markdown, HTML\\)\n"
        . "✅ And much more!";

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => $features,
        'parse_mode' => 'MarkdownV2'
    ]);
}

function handleContact(TelegramBot $bot, int $chatId): void
{
    // Auto-escaped contact info!
    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => "📞 *Contact Information*\n\n"
            . "👨‍💻 Developer: @username\n"
            . "🌐 Website: https://example.com\n"
            . "📧 Email: contact@example.com",
        'parse_mode' => 'MarkdownV2'
    ]);
}

function handleCallbackQuery(TelegramBot $bot, array $callbackQuery): void
{
    $chatId = $callbackQuery['message']['chat']['id'];
    $messageId = $callbackQuery['message']['message_id'];
    $data = $callbackQuery['data'];
    $queryId = $callbackQuery['id'];

    // Answer the callback query to remove loading state
    $bot->api()->call(
        ApiMethod::ANSWER_CALLBACK_QUERY,
        ['callback_query_id' => $queryId]
    );

    // Handle different callback data
    switch ($data) {
        case 'cmd_help':
            handleHelp($bot, $chatId);
            break;

        case 'cmd_features':
            handleFeatures($bot, $chatId);
            break;

        case 'cmd_contact':
            handleContact($bot, $chatId);
            break;

        case 'btn_like':
            $bot->api()->call(
                ApiMethod::EDIT_MESSAGE_TEXT,
                [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => '😊 Thanks for liking!'
                ]
            );
            break;

        case 'btn_dislike':
            $bot->api()->call(
                ApiMethod::EDIT_MESSAGE_TEXT,
                [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'text' => "😔 We'll do better next time!"
                ]
            );
            break;

        default:
            $bot->api()->call(
                ApiMethod::ANSWER_CALLBACK_QUERY,
                [
                    'callback_query_id' => $queryId,
                    'text' => 'Unknown action',
                    'show_alert' => true
                ]
            );
    }
}

// Main bot loop
try {
    $bot = new TelegramBot();

    echo "Commands Bot started...\n";
    echo "Commands: /start, /help, /keyboard, /photo, /dice\n";
    echo "Press Ctrl+C to stop\n\n";

    $offset = 0;

    while (true) {
        try {
            $updates = $bot->getUpdates([
                'offset' => $offset,
                'timeout' => 30
            ]);

            foreach ($updates as $update) {
                $offset = $update['update_id'] + 1;

                // Handle callback queries
                if (isset($update['callback_query'])) {
                    handleCallbackQuery($bot, $update['callback_query']);
                    continue;
                }

                // Handle messages
                if (isset($update['message'])) {
                    $message = $update['message'];
                    $chatId = $message['chat']['id'];
                    $text = $message['text'] ?? '';
                    $firstName = $message['from']['first_name'] ?? '';

                    // Check for commands
                    if (strpos($text, '/') === 0) {
                        $command = explode(' ', $text)[0];

                        switch ($command) {
                            case '/start':
                                handleStart($bot, $chatId, $firstName);
                                break;

                            case '/help':
                                handleHelp($bot, $chatId);
                                break;

                            case '/keyboard':
                                handleKeyboard($bot, $chatId);
                                break;

                            case '/photo':
                                handlePhoto($bot, $chatId);
                                break;

                            case '/dice':
                                handleDice($bot, $chatId);
                                break;

                            default:
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => "Unknown command: $command\nType /help to see available commands.",
                                    'parse_mode' => 'MarkdownV2'
                                ]);
                        }
                    } else {
                        // Handle regular text messages
                        switch ($text) {
                            case '👍 Like':
                            case 'Like':
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => '😊 Thanks for liking!'
                                ]);
                                break;

                            case '👎 Dislike':
                            case 'Dislike':
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => "😔 We'll do better next time!"
                                ]);
                                break;

                            case '❓ Help':
                            case 'Help':
                                handleHelp($bot, $chatId);
                                break;

                            case '🎲 Random':
                            case 'Random':
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => '🎲 Random number: ' . rand(1, 100)
                                ]);
                                break;

                            default:
                                // Auto-escaped default response!
                                $bot->messages()->send([
                                    'chat_id' => $chatId,
                                    'text' => "You said: *$text*\n\nType /help to see available commands.",
                                    'parse_mode' => 'MarkdownV2'
                                ]);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            echo "Error: " . $e->getMessage() . "\n";
            sleep(5);
        }
    }
} catch (\Throwable $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
