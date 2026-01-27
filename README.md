# Pure PHP Telegram Bot

A lightweight, dependency-free PHP library for creating Telegram bots. This library uses pure PHP with no external dependencies or frameworks required.

## Requirements

- PHP 8.0 or higher (8.3+ recommended)
- Extensions: `curl`, `json`, `mbstring`, `openssl`, `fileinfo`

### Checking Extensions

Verify required extensions are enabled:

```bash
php -m | grep -E "(curl|json|mbstring|openssl|fileinfo)"
```

To enable an extension, edit your `php.ini` file and uncomment the relevant line:

```ini
extension=curl
extension=mbstring
extension=openssl
extension=fileinfo
```

## Installation

1. Clone or download this repository:

```bash
git clone <repository-url>
cd tg-bots
```

1. Copy the example environment file and configure your bot token:

```bash
cp .env.example .env
```

1. Edit `.env` and add your bot token from [@BotFather](https://t.me/BotFather):

```bash
TELEGRAM_BOT_TOKEN=your_actual_bot_token_here
```

1. Load the environment variables in your script:

```php
// Load .env file (manual loading since we're dependency-free)
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
        putenv(trim($name) . '=' . trim($value));
    }
}
```

## Quick Start

### Long Polling (Recommended for Testing)

```php
<?php
require_once __DIR__ . '/src/TelegramBot.php';

// Load environment variables
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        $_ENV[trim($name)] = trim($value);
    }
}

$bot = new TelegramBot();

// Get updates
$offset = 0;
while (true) {
    $updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 30]);

    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;

        if (isset($update['message'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'] ?? '';

            $bot->sendMessage([
                'chat_id' => $chatId,
                'text' => "You said: $text"
            ]);
        }
    }

    sleep(1);
}
```

### Webhook (Recommended for Production)

1. Set up your webhook:

```php
<?php
require_once __DIR__ . '/src/TelegramBot.php';

$bot = new TelegramBot();

$result = $bot->setWebhook([
    'url' => 'https://your-domain.com/public/webhook.php'
]);

print_r($result);
```

1. Deploy `public/webhook.php` to your server and handle incoming updates.

## API Methods

### Message Sending

- `sendMessage(array $params)` - Send text messages
- `editMessageText(array $params)` - Edit message text
- `editMessageCaption(array $params)` - Edit message caption
- `deleteMessage(array $params)` - Delete messages
- `forwardMessage(array $params)` - Forward messages
- `copyMessage(array $params)` - Copy messages

### Bulk Operations

- `sendMessagesBulk(array $messagesArray, array $options = [])` - Send multiple messages in parallel using `curl_multi_exec`
- `broadcastMessage(array $chatIds, string $text, array $commonParams = [], array $options = [])` - Send the same message to multiple users

#### Bulk Messaging Examples

Send different messages to multiple users simultaneously:

```php
$results = $bot->sendMessagesBulk([
    ['chat_id' => 123456789, 'text' => 'Hello User 1'],
    ['chat_id' => 987654321, 'text' => 'Hello User 2'],
    ['chat_id' => 555555555, 'text' => 'Hello User 3']
]);

echo "Sent {$results['successful']}/{$results['total']} messages\n";

// Check individual results
foreach ($results['results'] as $result) {
    if ($result['success']) {
        echo "✅ Sent to {$result['chat_id']} (message_id: {$result['message_id']})\n";
    } else {
        echo "❌ Failed for {$result['chat_id']}: {$result['error']}\n";
    }
}
```

Broadcast the same message to multiple users:

```php
$chatIds = [123, 456, 789, 101, 202];

$results = $bot->broadcastMessage(
    $chatIds,
    'Important announcement!',
    ['parse_mode' => 'Markdown'],
    ['max_concurrent' => 20, 'delay_ms' => 500]
);

echo "Broadcast: {$results['successful']}/{$results['total']} delivered\n";
```

**Return Structure:**

```php
[
    'success' => bool,           // true if all messages succeeded
    'total' => int,              // Total messages attempted
    'successful' => int,         // Count of successful sends
    'failed' => int,             // Count of failed sends
    'results' => [               // Individual results (indexed by input position)
        0 => [
            'success' => bool,
            'chat_id' => int|string,
            'message_id' => int|null,
            'data' => array|null,      // Full API response if successful
            'error' => string|null     // Error message if failed
        ],
        // ... more results
    ],
    'errors' => array           // Array of error messages from failed sends
]
```

**Options:**

```php
$options = [
    'max_concurrent' => 30,     // Max parallel requests (default: 30)
    'delay_ms' => 0             // Delay between batches in milliseconds
]
```

**Note:** Individual message failures don't abort the batch. All requests are executed, and you can check each result individually.

### Media Sending

- `sendPhoto(array $params)` - Send photos
- `sendDocument(array $params)` - Send documents
- `sendVideo(array $params)` - Send videos
- `sendAudio(array $params)` - Send audio files
- `sendVoice(array $params)` - Send voice messages
- `sendAnimation(array $params)` - Send GIFs
- `sendSticker(array $params)` - Send stickers
- `sendLocation(array $params)` - Send locations
- `sendVenue(array $params)` - Send venues
- `sendContact(array $params)` - Send contacts
- `sendPoll(array $params)` - Send polls
- `sendDice(array $params)` - Send dice

### Chat Actions

- `sendChatAction(array $params)` - Send chat actions (typing, uploading photo, etc.)

### Interactive Features

- `answerCallbackQuery(array $params)` - Answer callback buttons
- `answerInlineQuery(array $params)` - Answer inline queries

### Bot Information

- `getMe()` - Get bot information
- `getChat(array $params)` - Get chat information
- `getChatMember(array $params)` - Get chat member information
- `getChatAdministrators(array $params)` - Get admin list
- `getChatMemberCount(array $params)` - Get member count

### Chat Administration

- `banChatMember(array $params)` - Ban user from chat
- `unbanChatMember(array $params)` - Unban user
- `kickChatMember(array $params)` - Kick user from chat
- `restrictChatMember(array $params)` - Restrict user permissions
- `promoteChatMember(array $params)` - Promote user to admin
- `leaveChat(array $params)` - Leave chat/group

### Message Management

- `pinChatMessage(array $params)` - Pin message
- `unpinChatMessage(array $params)` - Unpin message
- `unpinAllChatMessages(array $params)` - Unpin all messages

### Chat Settings

- `setChatTitle(array $params)` - Set chat title
- `setChatDescription(array $params)` - Set chat description
- `setChatPhoto(array $params)` - Set chat photo
- `deleteChatPhoto(array $params)` - Delete chat photo
- `setChatPermissions(array $params)` - Set chat permissions

### Webhook Management

- `setWebhook(array $params)` - Set webhook URL
- `getWebhookInfo()` - Get current webhook info
- `deleteWebhook(array $params = [])` - Remove webhook

### Updates

- `getUpdates(array $params = [])` - Get updates (long polling)

### Games

- `sendGame(array $params)` - Send games
- `setGameScore(array $params)` - Set game score
- `getGameHighScores(array $params)` - Get high scores

### Payments

- `createInvoice(array $params)` - Send invoices

## Keyboards

### Inline Keyboard

```php
$keyboard = $bot->buildInlineKeyboard([
    [
        ['text' => 'Button 1', 'callback_data' => 'btn1'],
        ['text' => 'Button 2', 'callback_data' => 'btn2']
    ],
    [
        ['text' => 'Open URL', 'url' => 'https://example.com']
    ]
]);

$bot->sendMessage([
    'chat_id' => $chatId,
    'text' => 'Choose an option:',
    'reply_markup' => $keyboard
]);
```

### Reply Keyboard

```php
$keyboard = $bot->buildReplyKeyboard([
    ['Option 1', 'Option 2'],
    ['Option 3', 'Option 4']
], [
    'resize_keyboard' => true,
    'one_time_keyboard' => true
]);

$bot->sendMessage([
    'chat_id' => $chatId,
    'text' => 'Choose an option:',
    'reply_markup' => $keyboard
]);
```

## Examples

The `examples/` directory contains several working examples:

- **echo.php** - Simple echo bot using long polling
- **commands.php** - Bot with /start and /help commands
- **webhook.php** - Webhook-based bot example
- **menu.php** - Complex inline keyboard menu with navigation
- **admin.php** - Group administration features
- **media.php** - Media file handling examples
- **bulk-test.php** - Bulk messaging demonstration using parallel cURL requests

Run an example:

```bash
php examples/echo.php
```

## Error Handling

The library throws exceptions on errors. Always wrap your bot calls in try-catch:

```php
try {
    $result = $bot->sendMessage(['chat_id' => $chatId, 'text' => 'Hello']);
} catch (Exception $e) {
    error_log('Error sending message: ' . $e->getMessage());
}
```

## Parse Mode

You can use different parse modes for formatted text:

```php
$bot->sendMessage([
    'chat_id' => $chatId,
    'text' => '*Bold* and _italic_ text',
    'parse_mode' => 'Markdown'
]);
```

Supported parse modes: `Markdown`, `MarkdownV2`, `HTML`

## File Uploads

To send files, you can use:

1. **File ID** (from previous uploads)
2. **URL** (Telegram will download the file)
3. **Local file path** (using CURLFile)

```php
// Using local file
$bot->sendPhoto([
    'chat_id' => $chatId,
    'photo' => new CURLFile('/path/to/photo.jpg'),
    'caption' => 'Photo caption'
]);

// Using URL
$bot->sendPhoto([
    'chat_id' => $chatId,
    'photo' => 'https://example.com/photo.jpg',
    'caption' => 'Photo caption'
]);

// Using file ID
$bot->sendPhoto([
    'chat_id' => $chatId,
    'photo' => 'AgACAgIAAxkBAAI...', // File ID from previous upload
    'caption' => 'Photo caption'
]);
```

## Security

- **Never commit your `.env` file** to version control
- **Always use environment variables** for sensitive data
- **Validate webhook requests** using a secret token
- **Keep your bot token secure** and rotate if compromised

## Troubleshooting

### Bot doesn't respond

1. Check your bot token is correct
2. Verify you've loaded the environment variables
3. Check error logs for exceptions
4. Ensure all required extensions are enabled: `curl`, `json`, `mbstring`, `openssl`, `fileinfo`

### Webhook not working

1. Verify your server is accessible from the internet
2. Check SSL certificate is valid (required for webhooks)
3. Use `getWebhookInfo()` to check webhook status
4. Test with long polling first to isolate issues

### Large file uploads fail

- Telegram has file size limits (photos: 10MB, documents: 50MB, videos: 50MB)
- Check PHP `upload_max_filesize` and `post_max_size` settings
- Ensure enough memory is available

## License

MIT License - Feel free to use this library in your projects.

## Resources

- [Telegram Bot API Documentation](https://core.telegram.org/bots/api)
- [@BotFather](https://t.me/BotFather) - Create and manage bots
- [Telegram Bots FAQ](https://core.telegram.org/bots/faq)
