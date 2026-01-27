<?php

/**
 * Commands Bot Example
 *
 * A bot with command handlers (/start, /help)
 * and inline keyboard buttons with callback query handling.
 */

require_once __DIR__ . '/../src/TelegramBot.php';

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
        ? "Hello! Welcome to the Commands Bot."
        : "Hello $firstName! Welcome to the Commands Bot.";

    $keyboard = $bot->buildInlineKeyboard([
        [
            $bot->createUrlButton('📚 Documentation', 'https://core.telegram.org/bots/api'),
            $bot->createCallbackButton('ℹ️ Help', 'cmd_help')
        ],
        [
            $bot->createCallbackButton('🎮 Features', 'cmd_features'),
            $bot->createCallbackButton('📞 Contact', 'cmd_contact')
        ]
    ]);

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $greeting . "\n\nChoose an option below:",
        'reply_markup' => $keyboard
    ]);
}

function handleHelp(TelegramBot $bot, int $chatId): void
{
    $helpText = "📖 *Available Commands*\n\n";
    $helpText .= "/start - Start the bot\n";
    $helpText .= "/help - Show this help message\n";
    $helpText .= "/keyboard - Show a custom keyboard\n";
    $helpText .= "/photo - Send a sample photo\n";
    $helpText .= "/dice - Roll a dice\n";

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $helpText,
        'parse_mode' => 'Markdown'
    ]);
}

function handleKeyboard(TelegramBot $bot, int $chatId): void
{
    $keyboard = $bot->buildReplyKeyboard(
        [
            ['👍 Like', '👎 Dislike'],
            ['❓ Help', '🎲 Random']
        ],
        [
            'resize_keyboard' => true,
            'one_time_keyboard' => false
        ]
    );

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => 'Here is a custom keyboard. Try the buttons!',
        'reply_markup' => $keyboard
    ]);
}

function handlePhoto(TelegramBot $bot, int $chatId): void
{
    // Send a photo from URL
    $bot->sendPhoto([
        'chat_id' => $chatId,
        'photo' => 'https://picsum.photos/800/600',
        'caption' => '📷 Here is a random photo from Lorem Picsum!',
        'show_caption_above_media' => true,
        'parse_mode' => 'HTML'
    ]);
}

function handleDice(TelegramBot $bot, int $chatId): void
{
    $bot->sendDice([
        'chat_id' => $chatId
    ]);
}

function handleFeatures(TelegramBot $bot, int $chatId): void
{
    $features = "🎮 *Bot Features*\n\n";
    $features .= "✅ Commands (/start, /help, etc.)\n";
    $features .= "✅ Inline keyboards\n";
    $features .= "✅ Callback queries\n";
    $features .= "✅ Custom reply keyboards\n";
    $features .= "✅ Media sending (photos, videos, etc.)\n";
    $features .= "✅ Message formatting (Markdown, HTML)\n";
    $features .= "✅ And much more!\n";

    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => $features,
        'parse_mode' => 'Markdown'
    ]);
}

function handleContact(TelegramBot $bot, int $chatId): void
{
    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => "📞 *Contact Information*\n\n"
            . "👨‍💻 Developer: @username\n"
            . "🌐 Website: https://example.com\n"
            . "📧 Email: contact@example.com",
        'parse_mode' => 'Markdown'
    ]);
}

function handleCallbackQuery(TelegramBot $bot, array $callbackQuery): void
{
    $chatId = $callbackQuery['message']['chat']['id'];
    $messageId = $callbackQuery['message']['message_id'];
    $data = $callbackQuery['data'];
    $queryId = $callbackQuery['id'];

    // Answer the callback query to remove loading state
    $bot->answerCallbackQuery([
        'callback_query_id' => $queryId
    ]);

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
            $bot->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => '😊 Thanks for liking!'
            ]);
            break;

        case 'btn_dislike':
            $bot->editMessageText([
                'chat_id' => $chatId,
                'message_id' => $messageId,
                'text' => '😔 We\'ll do better next time!'
            ]);
            break;

        default:
            $bot->answerCallbackQuery([
                'callback_query_id' => $queryId,
                'text' => 'Unknown action',
                'show_alert' => true
            ]);
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
                                $bot->sendMessage([
                                    'chat_id' => $chatId,
                                    'text' => "Unknown command: $command\nType /help to see available commands."
                                ]);
                        }
                    } else {
                        // Handle regular text messages
                        switch ($text) {
                            case '👍 Like':
                            case 'Like':
                                $bot->sendMessage([
                                    'chat_id' => $chatId,
                                    'text' => '😊 Thanks for liking!'
                                ]);
                                break;

                            case '👎 Dislike':
                            case 'Dislike':
                                $bot->sendMessage([
                                    'chat_id' => $chatId,
                                    'text' => '😔 We\'ll do better next time!'
                                ]);
                                break;

                            case '❓ Help':
                            case 'Help':
                                handleHelp($bot, $chatId);
                                break;

                            case '🎲 Random':
                            case 'Random':
                                $bot->sendMessage([
                                    'chat_id' => $chatId,
                                    'text' => '🎲 Random number: ' . rand(1, 100)
                                ]);
                                break;

                            default:
                                $bot->sendMessage([
                                    'chat_id' => $chatId,
                                    'text' => "You said: *$text*\n\nType /help to see available commands.",
                                    'parse_mode' => 'Markdown'
                                ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            echo "Error: " . $e->getMessage() . "\n";
            sleep(5);
        }
    }
} catch (Exception $e) {
    echo "Fatal error: " . $e->getMessage() . "\n";
    exit(1);
}
