# Pure PHP Telegram Bot Framework

A modern, dependency-free PHP 8.1+ framework for creating Telegram bots with clean service-oriented architecture.

## ⚡ Features

- **Zero Dependencies** - Pure PHP, no external libraries required
- **Modern PHP** - Built for PHP 8.1+ with 8.3+ features
- **Service-Oriented** - Clean separation of concerns
- **Auto-Escaping** - MarkdownV2 special characters escaped automatically
- **Type Safe** - Strict types, readonly properties, enums throughout
- **Bulk Operations** - Parallel requests with `curl_multi_exec`
- **PSR-3 Logging** - Optional structured logging

## 📋 Requirements

- **PHP 8.1 or higher** (8.3+ recommended)
- **Extensions:** `curl`, `json`, `mbstring`, `openssl`, `fileinfo`

## 🚀 Quick Start

### Installation

```bash
git clone <repository-url>
cd tg-bots
cp .env.example .env
```

Edit `.env` and add your bot token from [@BotFather](https://t.me/BotFather):

```bash
TELEGRAM_BOT_TOKEN=your_actual_bot_token_here
```

### Basic Usage

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

// EnvLoader loads .env automatically
$bot = new TelegramBot();

// Simple echo
$updates = $bot->getUpdates();

foreach ($updates as $update) {
    if (isset($update['message'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'] ?? '';

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => "You said: $text",
            'parse_mode' => 'MarkdownV2'  // Auto-escaping!
        ]);
    }
}
```

## 🏗️ Architecture

```
Application Layer (User Code)
         ↓
Facade Layer (TelegramBot)
  - Service Accessors
  - Webhook Handling
         ↓
Service Layer
  - MessageService (auto-escaping)
  - MediaService
  - ChatService
  - WebhookService
         ↓
API Layer (ApiService)
  - Method Routing
  - Bulk Operations
         ↓
Client Layer (HttpClientInterface)
  - CurlHttpClient (default)
  - StreamHttpClient (fallback)
```

## 📚 Service API

### MessageService

```php
// Auto-escaping for MarkdownV2
$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => '*Bold* and _italic_',  // Auto-escaped!
    'parse_mode' => 'MarkdownV2'
]);

// Raw (no auto-escape) - use for pre-formatted text
$bot->messages()->sendRaw([
    'chat_id' => $chatId,
    'text' => '*Bold* and _italic_',  // Preserved as-is
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

### MediaService

```php
// Send photo with auto-escaped caption
$bot->media()->sendPhoto([
    'chat_id' => $chatId,
    'photo' => 'https://example.com/photo.jpg',
    'caption' => 'Check out this *great* photo!',
    'parse_mode' => 'MarkdownV2'
]);

// Send document
$bot->media()->sendDocument([
    'chat_id' => $chatId,
    'document' => new CURLFile('/path/to/file.pdf'),
    'caption' => 'Document with [link](url)'
]);
```

### ChatService

```php
// Ban member
$bot->chats()->banMember([
    'chat_id' => $chatId,
    'user_id' => $userId
]);

// Promote to admin
$bot->chats()->promoteMember([
    'chat_id' => $chatId,
    'user_id' => $userId,
    'can_change_info' => true,
    'can_delete_messages' => true
]);
```

### WebhookService

```php
// Set webhook
$bot->webhooks()->set([
    'url' => 'https://your-domain.com/public/webhook.php',
    'secret_token' => 'your_secret_token'
]);

// Get webhook info
$info = $bot->webhooks()->getInfo();

// Delete webhook
$bot->webhooks()->delete();
```

## 🔄 Bulk Operations

### Send Different Messages

```php
$results = $bot->messages()->sendBulk([
    ['chat_id' => 123, 'text' => 'Hello User 1'],
    ['chat_id' => 456, 'text' => 'Hello User 2'],
    ['chat_id' => 789, 'text' => 'Hello User 3']
]);

echo "Sent {$results['successful']}/{$results['total']} messages\n";
```

### Broadcast Same Message

```php
$results = $bot->messages()->broadcast(
    [123, 456, 789],
    'Announcement!',
    ['parse_mode' => 'MarkdownV2']
);
```

## ⌨️ Keyboards

### Inline Keyboard

```php
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;

$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::callback('Button 1', 'data_1'),
        Button::url('Google', 'https://google.com')
    )
    ->build();

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Choose:',
    'reply_markup' => $keyboard
]);
```

### Reply Keyboard

```php
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;

$keyboard = ReplyKeyboardBuilder::create()
    ->addButton('Option 1')
    ->addButton('Option 2')
    ->nextRow()
    ->addButton('Option 3')
    ->setOptions(
        ReplyKeyboardOptions::create()
            ->resize()
            ->oneTime()
    )
    ->build();
```

## 🎨 Text Formatting

### MarkdownV2 (Auto-Escaped)

```php
$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Text with *special* _characters_!',
    'parse_mode' => 'MarkdownV2'
]);
```

### HTML Formatter

```php
use AhmCho\Telegram\Formatting\HtmlFormatter;

$formatter = new HtmlFormatter();
$text = $formatter->bold('Bold') . ' ' . $formatter->italic('Italic');

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => 'HTML'
]);
```

## 🔧 Configuration

```php
use AhmCho\Telegram\Config\BotConfig;

$config = new BotConfig(
    token: 'your_token',
    apiUrl: 'https://api.telegram.org/bot',  // Optional
    throwExceptions: true,                      // Optional
    loggingEnabled: true,                        // Optional
    logLevel: 'INFO',                           // Optional
    logFilePath: 'logs/bot.log'                 // Optional
);

$bot = new TelegramBot(null, $config);
```

## 📡 Webhook Setup

### Set Webhook

```bash
php examples/setup-webhook.php set https://your-domain.com/webhook.php
```

### Handle Updates

```php
// webhook.php
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

## 🧪 Examples

Run examples to test features:

```bash
# Echo bot (long polling)
php examples/echo.php

# Media handling
php examples/media.php

# Admin features
php examples/admin.php

# Bulk messaging
php examples/bulk-test.php

# Webhook bot
php examples/webhook.php
```

## 🛡️ Error Handling

```php
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Exception\TelegramException;

try {
    $result = $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Hello']);
} catch (ApiException $e) {
    // Telegram API error (4xx, 5xx)
    error_log("API Error: {$e->getMessage()}");
} catch (HttpClientException $e) {
    // HTTP layer error (network, DNS, timeout)
    error_log("HTTP Error: {$e->getMessage()}");
} catch (TelegramException $e) {
    // Any framework error
    error_log("Framework Error: {$e->getMessage()}");
}
```

## 🔒 Security

- **Never commit `.env`** to version control
- **Use HTTPS** for webhooks (required by Telegram)
- **Validate secret tokens** for webhooks
- **Keep bot tokens secure** - rotate if compromised

## 📊 Performance

- **Bulk operations** use `curl_multi_exec` for parallel requests
- **Default: 30 concurrent** requests (configurable)
- **PHP 8.3+ json_validate()** for efficient JSON parsing
- **Readonly properties** for memory efficiency

## 🆕 Modern PHP Features

- **Constructor property promotion** (PHP 8.0)
- **Readonly properties/classes** (PHP 8.1+)
- **Enums** for type safety (PHP 8.1+)
- **Match expressions** (PHP 8.0)
- **Named arguments** (PHP 8.0)
- **Spread operator** for arrays (PHP 8.0)
- **json_validate()** (PHP 8.3)
- **Null safety** improvements (PHP 8.0+)

## 📖 API Reference

### Available Services

```php
$bot->messages()   // MessageService
$bot->media()       // MediaService
$bot->chats()       // ChatService
$bot->webhooks()    // WebhookService
$bot->api()         // ApiService (direct API calls)
$bot->formatter()   // MarkdownV2Formatter
```

### Common Methods

- `getMe()` - Bot information
- `getUpdates()` - Long polling
- `getWebhookUpdates()` - Parse webhook input
- `processWebhook()` - Process via handler

## 🐛 Troubleshooting

### Bot doesn't respond

1. Check bot token is correct in `.env`
2. Verify required PHP extensions are enabled
3. Check error logs for exceptions
4. Test with simple message first

### Webhook not working

1. Verify server accessibility from internet
2. Check SSL certificate is valid
3. Use `getWebhookInfo()` to check status
4. Test with long polling first

### SSL certificate errors

The framework disables SSL verification by default. If you need custom SSL settings:

```php
$config = new BotConfig(
    token: 'your_token',
    // Custom configuration if needed
);
```

## 📄 License

MIT License - Feel free to use in your projects.

## 🔗 Resources

- [Telegram Bot API Documentation](https://core.telegram.org/bots/api)
- [@BotFather](https://t.me/BotFather) - Create and manage bots
- [Telegram Bots FAQ](https://core.telegram.org/bots/faq)
