# Quick Start Guide - Database Examples

## Prerequisites

Before running the database examples, you need to set your Telegram bot token.

### Option 1: Set Environment Variable (Linux/Mac/Git Bash)

```bash
export TELEGRAM_BOT_TOKEN='your_bot_token_here'
```

### Option 2: Set Environment Variable (Windows CMD)

```cmd
set TELEGRAM_BOT_TOKEN=your_bot_token_here
```

### Option 3: Set Environment Variable (Windows PowerShell)

```powershell
$env:TELEGRAM_BOT_TOKEN='your_bot_token_here'
```

## Running the Examples

### 1. Database Example Bot

Interactive bot with database integration:

```bash
php examples/database-example.php
```

**Features:**
- `/start` - Register and see available commands
- `/stats` - View database statistics
- `/me` - View your stored information
- `/broadcast_active` - Send message to active users (last 7 days)
- `/broadcast_premium` - Send message to premium users

### 2. Database Broadcast Tool

Interactive bulk messaging tool:

```bash
php examples/database-broadcast.php
```

**Features:**
- Broadcast to all users
- Broadcast to active users (custom time range)
- Broadcast to premium users
- Broadcast to users with username
- Combine multiple filters
- Rate limiting and error handling

### 3. Database Statistics Dashboard

Analytics and reporting tool:

```bash
php examples/database-stats.php
```

**Features:**
- Overall statistics
- User growth over time
- Active user breakdown
- Premium user reports
- Search by username or ID
- Export to CSV

## Getting a Bot Token

If you don't have a bot token yet:

1. Open Telegram and search for **@BotFather**
2. Send `/newbot` command
3. Follow the prompts to create your bot
4. Copy the token provided by BotFather
5. Set it as your `TELEGRAM_BOT_TOKEN`

## Webhook Setup (Optional)

For production use, you can set up a webhook instead of long polling:

```bash
php examples/setup-webhook.php
```

Then point your webhook URL to the appropriate script.

## Troubleshooting

### Error: "TELEGRAM_BOT_TOKEN environment variable not set"

Make sure you've set the environment variable before running the script:

```bash
# Check if it's set
echo $TELEGRAM_BOT_TOKEN  # Linux/Mac
echo %TELEGRAM_BOT_TOKEN% # Windows CMD
echo $env:TELEGRAM_BOT_TOKEN # Windows PowerShell

# If empty, set it again
export TELEGRAM_BOT_TOKEN='your_token_here'
```

### Error: "PDO extension is not enabled"

The PDO SQLite extension should already be enabled. If not:

1. Find your php.ini file: `php --ini`
2. Edit it and uncomment: `extension=pdo_sqlite`
3. Restart your terminal

### Database File Location

Database files are stored in the `data/` directory by default:
- `data/bot.db` - Main bot database
- `data/test.db` - Test database

You can specify a custom path:

```php
$database = new Database('/path/to/custom.db');
```

## Example Usage in Your Code

```php
<?php

require_once 'src/TelegramBot.php';
require_once 'src/Database.php';

// Initialize with token from environment
$bot = new TelegramBot();

// Optional: Add database
$database = new Database(__DIR__ . '/data/mybot.db');
$bot->setDatabase($database);

// Get updates
$update = $bot->getWebhookUpdates();

if ($update && isset($update['message'])) {
    // Auto-save user to database
    $bot->saveUserFromUpdate($update);

    $chatId = $update['message']['chat']['id'];
    $text = $update['message']['text'];

    // Reply to user
    $bot->sendMessage([
        'chat_id' => $chatId,
        'text' => "You said: $text"
    ]);
}
```

## Next Steps

1. ✅ Set your `TELEGRAM_BOT_TOKEN`
2. ✅ Run `php examples/database-example.php`
3. ✅ Try the commands in Telegram
4. ✅ Explore `database-broadcast.php` for bulk messaging
5. ✅ Use `database-stats.php` to analyze your users

For full documentation, see `DATABASE_FEATURE.md`.
