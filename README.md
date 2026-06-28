<div align="center">

# 🤖 AhmCho\Telegram

**Modern PHP 8.1+ Telegram Bot Framework**

A lightweight, dependency-free framework with clean service-oriented architecture

[![PHP Version](https://img.shields.io/badge/php-8.1%2B-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)
[![Zero Dependencies](https://img.shields.io/badge/dependencies-zero-brightgreen.svg)]()
[![Type Safe](https://img.shields.io/badge/types-strict-red.svg)]()

</div>

---

## ✨ Features

- **🎯 Zero Dependencies** - Pure PHP, no external libraries required
- **🚀 Modern PHP** - Built for PHP 8.1+ with 8.3+ features throughout
- **🏗️ Service-Oriented** - Clean separation with dedicated services
- **🔒 Type Safe** - Strict types, readonly properties, enums
- **⚡ Auto-Escaping** - MarkdownV2 special characters handled automatically
- **🔄 Bulk Operations** - Parallel requests with `curl_multi_exec`
- **📝 Command System** - Built-in command routing with middleware
- **🔁 Retry Logic** - Automatic retry with exponential backoff
- **📊 PSR-3 Logging** - Optional structured logging
- **🛡️ Production Ready** - Error handling, rate limiting, SSL configuration

---

## 📋 Requirements

- **PHP 8.1 or higher** (8.3+ recommended)
- **Extensions:** `curl`, `json`, `mbstring`, `openssl`, `fileinfo`

---

## 🚀 Installation

```bash
# Clone the repository
git clone https://github.com/yourusername/tg-bots.git
cd tg-bots

# Copy environment file
cp .env.example .env

# Edit .env and add your bot token from @BotFather
# TELEGRAM_BOT_TOKEN=your_actual_bot_token_here
```

---

## 🎯 Quick Start

### Basic Echo Bot

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot(); // EnvLoader loads .env automatically

while (true) {
    $updates = $bot->getUpdates();
    
    foreach ($updates as $update) {
        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';
            
            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "You said: $text"
            ]);
        }
    }
}
```

### Command Handler

```php
<?php
$bot = new TelegramBot();

// Register commands
$bot->commands()
    ->register('start', fn($bot, $chatId, $args) => 
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome!'])
    )
    ->register('help', fn($bot, $chatId, $args) => 
        $bot->commands()->sendHelp($chatId)
    );

// Handle updates
foreach ($updates as $update) {
    $bot->commands()->handleUpdate($update);
}
```

### With Retry Logic

```php
<?php
// Send with automatic retry on failure
$result = $bot->sendMessageWithRetry(
    ['chat_id' => $chatId, 'text' => 'Important message'],
    ['max_retries' => 3, 'initial_delay_ms' => 1000]
);
```

---

## 🏗️ Architecture

```
┌─────────────────────────────────────┐
│   Application Layer (Your Code)     │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Facade Layer (TelegramBot)        │
│  • Service Accessors                 │
│  • Command Handler                   │
│  • Retry Logic                       │
│  • Webhook Handling                  │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Service Layer                      │
│  • MessageService (auto-escaping)    │
│  • MediaService                      │
│  • ChatService                       │
│  • WebhookService                    │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   API Layer (ApiService)             │
│  • Method Routing                    │
│  • Bulk Operations                  │
└──────────────┬──────────────────────┘
               │
┌──────────────▼──────────────────────┐
│   Client Layer                       │
│  • CurlHttpClient (default)          │
│  • StreamHttpClient (fallback)       │
└─────────────────────────────────────┘
```

---

## 📚 Services

### MessageService

Handles text messages with automatic MarkdownV2 escaping.

```php
// Send with auto-escaping
$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Text with *bold* and _italic_',  // Auto-escaped!
    'parse_mode' => 'MarkdownV2'
]);

// Send raw (no escaping) - for pre-formatted text
$bot->messages()->sendRaw([
    'chat_id' => $chatId,
    'text' => '*Already formatted*',  // Preserved as-is
    'parse_mode' => 'MarkdownV2'
]);

// Edit message
$bot->messages()->editText([
    'chat_id' => $chatId,
    'message_id' => $messageId,
    'text' => 'Updated text'
]);

// Delete message
$bot->messages()->delete([
    'chat_id' => $chatId,
    'message_id' => $messageId
]);
```

**Special Characters Auto-Escaped:** `\ _ * [ ] ( ) ~ ` > # + - = | { } . !`

### MediaService

Send media files with caption auto-escaping.

```php
// Send photo
$bot->media()->sendPhoto([
    'chat_id' => $chatId,
    'photo' => 'https://example.com/photo.jpg',
    'caption' => 'Check out this *photo*!',
    'parse_mode' => 'MarkdownV2'
]);

// Send document
$bot->media()->sendDocument([
    'chat_id' => $chatId,
    'document' => new CURLFile('/path/to/file.pdf'),
    'caption' => 'Document with [link](https://example.com)'
]);

// Send video
$bot->media()->sendVideo([
    'chat_id' => $chatId,
    'video' => 'https://example.com/video.mp4',
    'caption' => 'Video description',
    'supports_streaming' => true
]);
```

### ChatService

Group and supergroup administration.

```php
// Ban member
$bot->chats()->banMember([
    'chat_id' => $chatId,
    'user_id' => $userId,
    'until_date' => time() + 86400  // 1 day
]);

// Promote to admin
$bot->chats()->promoteMember([
    'chat_id' => $chatId,
    'user_id' => $userId,
    'can_change_info' => true,
    'can_delete_messages' => true,
    'can_invite_users' => true
]);

// Get member count
$count = $bot->chats()->getMemberCount(['chat_id' => $chatId]);
```

### WebhookService

Webhook management for production deployments.

```php
// Set webhook
$bot->webhooks()->set([
    'url' => 'https://your-domain.com/webhook.php',
    'secret_token' => 'your_secret_token'
]);

// Get webhook info
$info = $bot->webhooks()->getInfo();

// Delete webhook
$bot->webhooks()->delete();
```

---

## 🎮 Command Handler

Built-in command routing system for easy bot command management.

```php
use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

// Register commands
$bot->commands()
    ->register('start', function ($bot, $chatId, $args) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '👋 Welcome to the bot!'
        ]);
    }, 'Start the bot')
    
    ->register('help', function ($bot, $chatId, $args) {
        $bot->commands()->sendHelp($chatId);
    }, 'Show available commands')
    
    ->register('ping', function ($bot, $chatId, $args) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => '🏓 Pong!'
        ]);
    }, 'Check bot responsiveness')
    
    // Add middleware
    ->addMiddleware('logging', function ($bot, $chatId, $command, $args) {
        error_log("Command /$command from $chatId");
        return true; // Continue to command
    })
    
    // Set default handler for unknown commands
    ->setDefault(function ($bot, $chatId, $command, $args) {
        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => "Unknown command: /$command\nType /help for commands."
        ]);
    });

// Handle updates in your loop
foreach ($updates as $update) {
    $bot->commands()->handleUpdate($update);
}
```

---

## 🔁 Retry & Rate Limit Handling

Automatic retry with exponential backoff and rate limit detection.

```php
// Simple retry with defaults
$result = $bot->sendMessageWithRetry([
    'chat_id' => $chatId,
    'text' => 'Important message'
]);

// Custom retry configuration
$result = $bot->sendMessageWithRetry(
    ['chat_id' => $chatId, 'text' => 'Critical message'],
    [
        'max_retries' => 5,
        'initial_delay_ms' => 2000,
        'max_delay_ms' => 30000,
        'on_retry' => function ($attempt, $error, $delayMs) {
            echo "Retry $attempt, waiting ${delayMs}ms...\n";
        }
    ]
);

// Bulk operations with retry
$results = $bot->sendBulkWithRetry(
    [
        ['chat_id' => $chatId, 'text' => 'Bulk 1'],
        ['chat_id' => $chatId, 'text' => 'Bulk 2'],
    ],
    ['max_concurrent' => 10],  // Bulk options
    ['max_retries' => 2]        // Retry options
);
```

**Features:**
- Exponential backoff between retries
- Automatic rate limit (429) detection and handling
- Respects `retry-after` header from Telegram
- Configurable retry attempts and delays
- Retry callbacks for monitoring

---

## 🔄 Bulk Operations

Send different messages to multiple users in parallel.

```php
// Send different messages
$results = $bot->messages()->sendBulk([
    ['chat_id' => 123, 'text' => 'Hello User 1'],
    ['chat_id' => 456, 'text' => 'Hello User 2'],
    ['chat_id' => 789, 'text' => 'Hello User 3']
]);

echo "Sent {$results->successful}/{$results->total}\n";
echo "Success rate: " . $results->getSuccessRate() . "%\n";

// Check individual results
foreach ($results->results as $result) {
    if ($result['success']) {
        echo "✓ Sent to {$result['chat_id']}\n";
    } else {
        echo "✗ Failed for {$result['chat_id']}: {$result['error']}\n";
    }
}
```

Broadcast same message to multiple chats:

```php
$results = $bot->messages()->broadcast(
    [123, 456, 789],
    'Important announcement!',
    ['parse_mode' => 'MarkdownV2'],
    ['max_concurrent' => 20, 'delay_ms' => 500]
);
```

**`BulkResult` object** (readonly class, not an array):
```php
$result->total;             // int — total attempted
$result->successful;        // int — count of successful sends
$result->failed;            // int — count of failed sends
$result->results;           // array of per-request results (see below)
$result->errors;            // array of error strings
$result->isSuccess();       // bool — true if failed === 0
$result->hasFailures();     // bool
$result->getSuccessRate();  // float — percentage (0–100)
$result->getFailedResults();
$result->getSuccessfulResults();
count($result);             // Countable

// Each entry in $result->results:
// ['success' => bool, 'chat_id' => int|string, 'message_id' => int|null, 'error' => string|null]
```

Throws `BulkSendException` (which carries the `BulkResult`) if any request fails and `throwExceptions` is enabled (default). Use `BotConfig(throwExceptions: false)` to suppress.

---

## ⌨️ Keyboards

### Inline Keyboard

```php
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;

$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::callback('✅ Approve', 'approve_123'),
        Button::callback('❌ Reject', 'reject_123')
    )
    ->addRow(
        Button::url('🌐 Website', 'https://example.com'),
        Button::callback('ℹ️ Info', 'info')
    )
    ->build();

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Choose an option:',
    'reply_markup' => $keyboard
]);
```

### Reply Keyboard

```php
use AhmCho\Telegram\Keyboard\Button;
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;

$keyboard = ReplyKeyboardBuilder::create(
    new ReplyKeyboardOptions(resizeKeyboard: true, oneTimeKeyboard: true)
)
    ->addRow(Button::text('Option 1'), Button::text('Option 2'))
    ->addRow(Button::text('Option 3'))
    ->build();
```

---

## 🎨 Text Formatting

### MarkdownV2 Formatter

```php
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;

$formatter = new MarkdownV2Formatter();

$text = $formatter->bold('Bold text')
    . ' ' . $formatter->italic('Italic text')
    . "\n\n"
    . $formatter->code('inline code')
    . ' - ' . $formatter->link('Link', 'https://example.com');

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => 'MarkdownV2'
]);
```

**Available Methods:**
- `bold($text)` - **bold**
- `italic($text)` - _italic_
- `code($text)` - `code`
- `pre($text)` - ```pre```
- `underline($text)` - __underline__
- `strikethrough($text)` - ~strikethrough~
- `spoiler($text)` - ||spoiler||
- `link($text, $url)` - [text](url)
- `textLink($text, $url)` - [text](url)
- `userMention($userId)` - [user](id)
- `codeLink($code, $url)` - [code](url)

### HTML Formatter

```php
use AhmCho\Telegram\Formatting\HtmlFormatter;

$formatter = new HtmlFormatter();

$text = $formatter->bold('Bold')
    . ' ' . $formatter->italic('Italic')
    . ' ' . $formatter->code('code');

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => 'HTML'
]);
```

---

## 🔧 Configuration

```php
use AhmCho\Telegram\Config\BotConfig;

$config = new BotConfig(
    token: 'your_bot_token',
    apiUrl: 'https://api.telegram.org/bot',  // Optional, default provided
    timeout: 30,                               // Request timeout in seconds
    throwExceptions: true,                      // Throw exceptions on errors
    loggingEnabled: true,                       // Enable logging
    logLevel: 'INFO',                          // Log level (DEBUG, INFO, WARNING, ERROR)
    logFilePath: 'logs/bot.log'                // Log file path
);

$bot = new TelegramBot(null, $config);
```

### Custom HTTP Client

```php
use AhmCho\Telegram\Client\CurlHttpClient;

$httpClient = new CurlHttpClient($config, $logger);
$bot = new TelegramBot(null, $config, $httpClient);
```

---

## 📡 Webhook Setup

### Using Setup Script

```bash
php examples/setup-webhook.php set https://your-domain.com/webhook.php
```

### Handle Webhook Updates

```php
<?php
// webhook.php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

$update = $bot->getWebhookUpdates();

if ($update && isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    
    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => "You said: $text"
    ]);
}
```

### Webhook with Command Handler

```php
<?php
$bot = new TelegramBot();

// Register commands
$bot->commands()
    ->register('start', fn($bot, $chatId, $args) => ...)
    ->register('help', fn($bot, $chatId, $args) => ...);

$update = $bot->getWebhookUpdates();

if ($update) {
    $bot->commands()->handleUpdate($update);
}
```

---

## 🧪 Examples

| Example | Description |
|---------|-------------|
| `echo.php` | Simple echo bot with long polling |
| `commands-demo.php` | Command handler system demo |
| `retry-demo.php` | Retry and rate limit handling |
| `media.php` | Media files handling |
| `admin.php` | Group administration features |
| `menu.php` | Complex inline keyboard menu |
| `bulk-test.php` | Bulk messaging demonstration |
| `webhook.php` | Webhook-based bot |

Run examples:
```bash
php examples/echo.php
php examples/commands-demo.php
php examples/retry-demo.php <chat_id>
```

---

## 🛡️ Error Handling

```php
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Exception\TelegramException;

try {
    $result = $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => 'Hello'
    ]);
} catch (ApiException $e) {
    // Telegram API error (4xx, 5xx)
    $errorCode = $e->getErrorCode();
    $httpCode = $e->getHttpCode();
    error_log("API Error [{$errorCode}]: {$e->getMessage()}");
    
} catch (HttpClientException $e) {
    // HTTP/network error
    error_log("HTTP Error: {$e->getMessage()}");
    
} catch (TelegramException $e) {
    // Any framework error
    error_log("Framework Error: {$e->getMessage()}");
}
```

---

## 🔒 Security Best Practices

1. **Never commit `.env`** to version control
2. **Use HTTPS** for webhooks (required by Telegram)
3. **Validate secret tokens** for webhook requests
4. **Keep bot tokens secure** - rotate if compromised
5. **Sanitize user input** before displaying or processing
6. **Use rate limiting** to prevent abuse
7. **Validate callback data** from inline keyboards

---

## 📊 Performance

- **Bulk Operations**: `curl_multi_exec` for parallel requests (default: 30 concurrent)
- **Memory Efficiency**: Readonly properties, lazy service initialization
- **JSON Validation**: PHP 8.3+ `json_validate()` for efficient parsing
- **SSL Verification**: Configurable for different environments
- **Connection Reuse**: HTTP client reuses connections

### Optimization Tips

```php
// Use bulk operations for multiple recipients
$bot->messages()->broadcast($chatIds, $message);

// Adjust concurrent requests for your server
$results = $bot->messages()->sendBulk($messages, [
    'max_concurrent' => 50,  // Increase for powerful servers
    'delay_ms' => 100        // Add delay between batches
]);

// Use raw methods when text is pre-formatted
$bot->messages()->sendRaw($params);  // Skip auto-escaping
```

---

## 🔍 Troubleshooting

### Bot Doesn't Respond

1. **Check token**: Verify `TELEGRAM_BOT_TOKEN` in `.env`
2. **Check extensions**: Ensure all required PHP extensions are enabled
3. **Test connection**: Use `getMe()` to verify API access
4. **Check logs**: Review log files for error messages

```bash
php -r "require 'autoload.php'; $b = new TelegramBot(); print_r($b->getMe());"
```

### Webhook Not Working

1. **Verify accessibility**: Ensure server is reachable from internet
2. **Check SSL**: Valid SSL certificate required (use `getWebhookInfo()`)
3. **Test with curl**: Verify webhook endpoint responds correctly
4. **Check path**: Webhook URL must point to your script

### Rate Limiting (429 Errors)

If you hit rate limits:
- Use `sendMessageWithRetry()` for automatic handling
- Implement rate limiting in your application
- Use bulk operations efficiently
- Respect Telegram's rate limits

### SSL Certificate Errors

The framework disables SSL verification by default. If you encounter errors:

```php
// Framework already disables SSL verification
// No additional configuration needed
```

---

## 📖 API Reference

### Service Accessors

```php
$bot->messages()      // MessageService - Text messages + auto-escaping
$bot->media()         // MediaService - Photos, videos, documents, audio
$bot->chats()         // ChatService - Group administration
$bot->webhooks()      // WebhookService - Webhook management
$bot->polls()         // PollsService - Polls and quizzes
$bot->inline()        // InlineService - Inline query responses
$bot->topics()        // TopicsService - Forum topic management
$bot->inviteLinks()   // InviteLinksService - Chat invite links
$bot->commands()      // CommandHandler - Command routing + middleware
$bot->api()           // ApiService - Direct API calls
$bot->formatter()     // TextFormatterInterface - Text formatting
```

### Common Methods

```php
// Bot information
$bot->getMe()

// Long polling
$bot->getUpdates(['offset' => $offset, 'timeout' => 30])

// Webhook updates
$bot->getWebhookUpdates()
$bot->processWebhook($handler)

// Retry methods
$bot->sendMessageWithRetry($params, $options)
$bot->sendBulkWithRetry($messages, $bulkOptions, $retryOptions)
$bot->executeWithRetry($callback, $options)
```

---

## 🚀 Modern PHP Features

The framework leverages modern PHP features:

| Feature | PHP Version | Usage |
|---------|-------------|-------|
| Constructor property promotion | 8.0 | Service constructors |
| Readonly properties | 8.1+ | Immutable configuration |
| Enums | 8.1+ | Type-safe constants |
| Match expressions | 8.0 | Clean conditional logic |
| Named arguments | 8.0 | Clearer API calls |
| Spread operator | 8.0 | Array merging |
| Null coalescing | 7.0+ | Safe default values |
| Type declarations | 7.0+ | Type safety |
| `json_validate()` | 8.3 | Efficient JSON parsing |
| `str_starts_with()` | 8.0 | String checking |

---

## 🤝 Contributing

Contributions are welcome! Please:

1. Fork the repository
2. Create a feature branch
3. Add tests for new features
4. Follow the existing code style
5. Submit a pull request

---

## 📄 License

MIT License - feel free to use in your projects.

---

## 🔗 Resources

- [Telegram Bot API Documentation](https://core.telegram.org/bots/api)
- [Telegram Bot API Changes](https://core.telegram.org/bots/api/changelog)
- [@BotFather](https://t.me/BotFather) - Create and manage bots
- [Telegram Bots FAQ](https://core.telegram.org/bots/faq)
- [Telegram Bots News Channel](https://t.me/botnews)

---

<div align="center">

**Built with ❤️ for the Telegram community**

</div>
