<?php
/**
 * Telegram Bot Webhook Endpoint
 *
 * This is the production webhook endpoint for hosting on a web server.
 * Configure your bot's webhook to point to this file.
 *
 * Setup:
 * 1. Deploy this file to your web server (e.g., https://your-domain.com/webhook.php)
 * 2. Ensure SSL is enabled (required by Telegram)
 * 3. Set the webhook using setup-webhook.php or manually:
 *    https://api.telegram.org/bot<TOKEN>/setWebhook?url=https://your-domain.com/webhook.php
 *
 * Security considerations:
 * - Use HTTPS (required by Telegram)
 * - Consider adding a secret token validation
 * - Implement rate limiting if needed
 */

// Error reporting (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 0); // Set to 1 for debugging, 0 for production

// Log errors to file
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/webhook_errors.log');

// Autoload the bot class
require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Bot\TelegramBot;

// Load environment variables (done automatically by TelegramBot constructor)

/**
 * Log webhook update for debugging
 *
 * @param array $update Update data
 * @return void
 */
function logUpdate(array $update): void
{
    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/webhook_updates.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] " . json_encode($update) . "\n";

    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

/**
 * Handle incoming update
 *
 * @param TelegramBot $bot Bot instance
 * @param array $update Update data
 * @return void
 */
function handleUpdate(TelegramBot $bot, array $update): void
{
    try {
        // Extract common data
        $chatId = null;
        $message = null;
        $callbackQuery = null;

        if (isset($update['message'])) {
            $message = $update['message'];
            $chatId = $message['chat']['id'];
        } elseif (isset($update['callback_query'])) {
            $callbackQuery = $update['callback_query'];
            $chatId = $callbackQuery['message']['chat']['id'];
        }

        if ($chatId === null) {
            return;
        }

        // ============================================
        // COMMAND HANDLERS
        // ============================================

        // Handle /start command
        if (isset($message['text']) && $message['text'] === '/start') {
            $keyboard = InlineKeyboardBuilder::create()
                ->addRow(
                    Button::callback('ℹ️ Help', 'help'),
                    Button::callback('📊 Stats', 'stats')
                )
                ->addRow(
                    Button::url('🌐 Visit Website', 'https://example.com')
                )
                ->build();

            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "👋 Welcome to the bot!\n\n"
                    . "I'm running on webhook mode, which is faster than long polling.\n\n"
                    . "Choose an option below:",
                'reply_markup' => $keyboard
            ]);

            return;
        }

        // Handle /help command
        if (isset($message['text']) && $message['text'] === '/help') {
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "ℹ️ *Help*\n\n"
                    . "Available commands:\n"
                    . "/start - Start the bot\n"
                    . "/help - Show this help\n"
                    . "/echo <text> - Echo back text\n"
                    . "/info - Get chat information\n\n"
                    . "This bot is running in webhook mode for optimal performance.",
                'parse_mode' => 'Markdown'
            ]);

            return;
        }

        // Handle /echo command
        if (isset($message['text']) && strpos($message['text'], '/echo ') === 0) {
            $text = substr($message['text'], 6);
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "📢 $text"
            ]);

            return;
        }

        // Handle /info command
        if (isset($message['text']) && $message['text'] === '/info') {
            try {
                $chat = $bot->chats()->getChat(['chat_id' => $chatId]);

                $info = "📊 *Chat Information*\n\n";
                $info .= "ID: `{$chat['id']}`\n";
                $info .= "Type: {$chat['type']}\n";

                if ($chat['type'] === 'private') {
                    $info .= "First Name: {$chat['first_name']}\n";
                    if (isset($chat['username'])) {
                        $info .= "Username: @{$chat['username']}\n";
                    }
                } elseif ($chat['type'] === 'group' || $chat['type'] === 'supergroup') {
                    $info .= "Title: {$chat['title']}\n";
                    if (isset($chat['username'])) {
                        $info .= "Username: @{$chat['username']}\n";
                    }
                }

                $bot->sendMessage([
                    'chat_id' => $chatId,
                    'text' => $info,
                    'parse_mode' => 'Markdown'
                ]);

            } catch (Exception $e) {
                $bot->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ Error getting info: " . $e->getMessage()
                ]);
            }

            return;
        }

        // Handle regular text messages
        if (isset($message['text'])) {
            $text = $message['text'];

            // Simple echo for non-command messages
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "You said: *$text*\n\nType /help to see available commands.",
                'parse_mode' => 'Markdown'
            ]);

            return;
        }

        // Handle photos
        if (isset($message['photo'])) {
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "📷 Thanks for the photo!"
            ]);

            return;
        }

        // Handle stickers
        if (isset($message['sticker'])) {
            $bot->media()->sendSticker([
                'chat_id' => $chatId,
                'sticker' => $message['sticker']['file_id']
            ]);

            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "😃 Nice sticker!"
            ]);

            return;
        }

        // Handle voice messages
        if (isset($message['voice'])) {
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "🎤 Voice message received!"
            ]);

            return;
        }

        // ============================================
        // CALLBACK QUERY HANDLERS
        // ============================================

        if ($callbackQuery !== null) {
            $data = $callbackQuery['data'];
            $queryId = $callbackQuery['id'];

            // Answer the callback query to remove loading state
            $bot->chats()->answerCallbackQuery(['callback_query_id' => $queryId]);

            $parts = explode(':', $data);
            $action = $parts[0] ?? '';

            switch ($action) {
                case 'help':
                    $bot->editMessageText([
                        'chat_id' => $chatId,
                        'message_id' => $callbackQuery['message']['message_id'],
                        'text' => "ℹ️ *Help*\n\n"
                            . "Available commands:\n"
                            . "/start - Start the bot\n"
                            . "/help - Show this help\n"
                            . "/echo <text> - Echo back text\n"
                            . "/info - Get chat information",
                        'parse_mode' => 'Markdown'
                    ]);
                    break;

                case 'stats':
                    try {
                        $chat = $bot->chats()->getChat(['chat_id' => $chatId]);

                        $stats = "📊 *Statistics*\n\n";

                        if (isset($chat['title'])) {
                            $stats .= "Name: {$chat['title']}\n";
                        }

                        $bot->editMessageText([
                            'chat_id' => $chatId,
                            'message_id' => $callbackQuery['message']['message_id'],
                            'text' => $stats,
                            'parse_mode' => 'Markdown'
                        ]);

                    } catch (Exception $e) {
                        $bot->chats()->answerCallbackQuery([
                            'callback_query_id' => $queryId,
                            'text' => 'Error getting stats: ' . $e->getMessage(),
                            'show_alert' => true
                        ]);
                    }
                    break;
            }

            return;
        }

    } catch (Exception $e) {
        // Log error
        error_log("Error handling update: " . $e->getMessage());

        // Try to notify user
        if (isset($chatId)) {
            try {
                $bot->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "❌ An error occurred. Please try again later."
                ]);
            } catch (Exception $e2) {
                error_log("Error notifying user: " . $e2->getMessage());
            }
        }
    }
}

// ============================================
// MAIN WEBHOOK HANDLER
// ============================================

try {
    // Only accept POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Method not allowed', 'method' => $_SERVER['REQUEST_METHOD']]);
        exit;
    }

    // Get POST input
    $input = file_get_contents('php://input');

    if (empty($input)) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Empty request body']);
        exit;
    }

    // Parse JSON
    $update = json_decode($input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid JSON', 'json_error' => json_last_error_msg()]);
        exit;
    }

    // Validate update structure
    if (!isset($update['update_id'])) {
        http_response_code(400);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Invalid update structure']);
        exit;
    }

    // Optional: Validate secret token if configured
    // $secretToken = getenv('TELEGRAM_WEBHOOK_SECRET');
    // if ($secretToken) {
    //     $receivedToken = $_SERVER['HTTP_X_TELEGRAM_BOT_API_SECRET_TOKEN'] ?? '';
    //     if ($receivedToken !== $secretToken) {
    //         http_response_code(403);
    //         exit;
    //     }
    // }

    // Log the update (disable in production for performance)
    // logUpdate($update);

    // Create bot instance
    $bot = new TelegramBot();

    // Handle the update
    handleUpdate($bot, $update);

    // Send success response
    http_response_code(200);
    header('Content-Type: text/plain');
    echo 'OK';

} catch (Exception $e) {
    // Log error
    error_log("Webhook error: " . $e->getMessage());

    // Send error response
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
