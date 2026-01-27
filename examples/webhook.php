<?php
/**
 * Webhook Bot Example
 *
 * This example demonstrates how to set up a webhook-based bot.
 * Webhooks are faster than long polling and are recommended for production.
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

// Command router
class CommandRouter
{
    private TelegramBot $bot;
    private array $commands = [];

    public function __construct(TelegramBot $bot)
    {
        $this->bot = $bot;
    }

    /**
     * Register a command handler
     *
     * @param string $command Command name (without /)
     * @param callable $handler Handler function
     * @return self
     */
    public function command(string $command, callable $handler): self
    {
        $this->commands[$command] = $handler;
        return $this;
    }

    /**
     * Handle an update
     *
     * @param array $update Update from Telegram
     * @return void
     */
    public function handle(array $update): void
    {
        try {
            // Handle callback queries
            if (isset($update['callback_query'])) {
                $this->handleCallbackQuery($update['callback_query']);
                return;
            }

            // Handle messages
            if (isset($update['message'])) {
                $this->handleMessage($update['message']);
            }

        } catch (Exception $e) {
            error_log("Error handling update: " . $e->getMessage());
        }
    }

    /**
     * Handle callback query
     *
     * @param array $callbackQuery
     * @return void
     */
    private function handleCallbackQuery(array $callbackQuery): void
    {
        $chatId = $callbackQuery['message']['chat']['id'];
        $data = $callbackQuery['data'];
        $queryId = $callbackQuery['id'];

        // Answer the callback query
        $this->bot->answerCallbackQuery([
            'callback_query_id' => $queryId,
            'text' => 'Processing...'
        ]);

        // Parse callback data as command
        $parts = explode(':', $data);
        $command = $parts[0];

        if (isset($this->commands[$command])) {
            call_user_func($this->commands[$command], $this->bot, $callbackQuery);
        } else {
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Unknown action'
            ]);
        }
    }

    /**
     * Handle message
     *
     * @param array $message
     * @return void
     */
    private function handleMessage(array $message): void
    {
        $chatId = $message['chat']['id'];
        $text = $message['text'] ?? '';

        if (empty($text)) {
            return;
        }

        // Check if it's a command
        if (strpos($text, '/') === 0) {
            $parts = explode(' ', $text);
            $command = str_replace('/', '', $parts[0]);
            $args = array_slice($parts, 1);

            if (isset($this->commands[$command])) {
                call_user_func($this->commands[$command], $this->bot, $message, $args);
            } else {
                $this->bot->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "Unknown command: /$command"
                ]);
            }
        } else {
            // Handle regular text
            $this->bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "You said: $text\n\nUse /help to see available commands."
            ]);
        }
    }
}

// Main webhook handler
function processWebhook(): void
{
    $bot = new TelegramBot();
    $router = new CommandRouter($bot);

    // Register command handlers
    $router->command('start', function($bot, $message, $args = []) {
        $chatId = $message['chat']['id'];
        $firstName = $message['from']['first_name'] ?? '';

        $keyboard = $bot->buildInlineKeyboard([
            [
                $bot->createCallbackButton('ℹ️ Help', 'help'),
                $bot->createCallbackButton('📊 Stats', 'stats')
            ],
            [
                $bot->createCallbackButton('⚙️ Settings', 'settings'),
                $bot->createCallbackButton('🎮 About', 'about')
            ]
        ]);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "Welcome $firstName!\n\n"
                . "I'm a webhook-based bot. I'm faster than long polling bots.\n\n"
                . "Choose an option:",
            'reply_markup' => $keyboard
        ]);
    });

    $router->command('help', function($bot, $message, $args = []) {
        $chatId = $message['chat']['id'];

        $help = "📖 *Available Commands*\n\n";
        $help .= "/start - Start the bot\n";
        $help .= "/help - Show this help\n";
        $help .= "/ping - Check bot response time\n";
        $help .= "/echo <text> - Echo back the text\n";
        $help .= "/info - Get chat information\n";

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => $help,
            'parse_mode' => 'Markdown'
        ]);
    });

    $router->command('ping', function($bot, $message, $args = []) {
        $chatId = $message['chat']['id'];
        $startTime = microtime(true);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => '⏱ Calculating...'
        ]);

        $endTime = microtime(true);
        $latency = round(($endTime - $startTime) * 1000, 2);

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "🏓 Pong!\n\n⚡ Webhook response time: {$latency}ms"
        ]);
    });

    $router->command('echo', function($bot, $message, $args = []) {
        $chatId = $message['chat']['id'];
        $text = implode(' ', $args);

        if (empty($text)) {
            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => 'Please provide text to echo. Usage: /echo <text>'
            ]);
            return;
        }

        $bot->sendMessage([
            'chat_id' => $chatId,
            'text' => "📢 $text"
        ]);
    });

    $router->command('info', function($bot, $message, $args = []) {
        $chatId = $message['chat']['id'];
        $chat = $bot->getChat(['chat_id' => $chatId]);

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
    });

    // Callback handlers
    $router->command('help', function($bot, $callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'];

        $bot->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery['message']['message_id'],
            'text' => "📖 *Available Commands*\n\n"
                . "/start - Start the bot\n"
                . "/help - Show this help\n"
                . "/ping - Check bot response time\n"
                . "/echo <text> - Echo back the text\n"
                . "/info - Get chat information\n",
            'parse_mode' => 'Markdown'
        ]);
    });

    $router->command('stats', function($bot, $callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'];

        $bot->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery['message']['message_id'],
            'text' => "📊 *Bot Statistics*\n\n"
                . "✅ Status: Online\n"
                . "⚡ Mode: Webhook\n"
                . "🕐 Uptime: " . date('H:i:s'),
            'parse_mode' => 'Markdown'
        ]);
    });

    $router->command('settings', function($bot, $callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'];

        $keyboard = $bot->buildInlineKeyboard([
            [
                $bot->createCallbackButton('🔔 Notifications', 'settings:notif'),
                $bot->createCallbackButton('🌐 Language', 'settings:lang')
            ],
            [
                $bot->createCallbackButton('🔙 Back', 'start')
            ]
        ]);

        $bot->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery['message']['message_id'],
            'text' => '⚙️ *Settings*\n\nChoose an option:',
            'parse_mode' => 'Markdown',
            'reply_markup' => $keyboard
        ]);
    });

    $router->command('about', function($bot, $callbackQuery) {
        $chatId = $callbackQuery['message']['chat']['id'];

        $bot->editMessageText([
            'chat_id' => $chatId,
            'message_id' => $callbackQuery['message']['message_id'],
            'text' => "🎮 *About This Bot*\n\n"
                . "This is a webhook-based Telegram bot built with pure PHP.\n\n"
                . "📦 No dependencies\n"
                . "⚡ Fast webhook response\n"
                . "🔧 Easy to customize\n",
            'parse_mode' => 'Markdown'
        ]);
    });

    // Get and process the update
    $update = $bot->getWebhookUpdates();
    if ($update !== null) {
        $router->handle($update);
        echo 'OK';
    } else {
        echo 'No update received';
    }
}

// Run the webhook handler
if (php_sapi_name() !== 'cli') {
    processWebhook();
} else {
    echo "This is a webhook bot. Deploy it to a web server.\n\n";

    // Setup webhook
    echo "To set up the webhook, run:\n";
    echo "php examples/setup-webhook.php <your-webhook-url>\n\n";

    // Or run in webhook setup mode
    if (isset($argv[1]) && $argv[1] === 'test') {
        $bot = new TelegramBot();

        echo "Testing webhook bot...\n";
        echo "Webhook URL not set. Use /setwebhook command.\n";

        // Get webhook info
        $webhookInfo = $bot->getWebhookInfo();
        echo "\nCurrent webhook info:\n";
        print_r($webhookInfo);
    }
}
