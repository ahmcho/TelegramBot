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
git clone https://github.com/ahmcho/TelegramBot.git tg-bots
cd tg-bots

# Copy environment file
cp .env.example .env

# Edit .env and add your bot token from @BotFather
# TELEGRAM_BOT_TOKEN=your_actual_bot_token_here
```

---

## 🎯 Quick Start

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot(); // EnvLoader loads .env automatically

while (true) {
    foreach ($bot->getUpdates() as $update) {
        if (isset($update['message'])) {
            $bot->messages()->send([
                'chat_id' => $update['message']['chat']['id'],
                'text' => 'You said: ' . ($update['message']['text'] ?? ''),
            ]);
        }
    }
}
```

---

## 📚 Full Documentation

This README is a quick pitch. For a complete, example-driven guide to
every feature — services, commands, webhooks, keyboards, formatting,
bulk operations, retry, error handling, logging, and a cookbook of
end-to-end recipes — see **[docs/README.md](docs/README.md)**, starting with
[Introduction](docs/01-introduction.md) and [Quickstart](docs/04-quickstart.md).

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
| `setup-webhook.php` | CLI script to set/delete the webhook |
| `logger-test.php` | PSR-3 logging integration |
| `ecommerce.php` | Product catalog and ordering flow |
| `multilanguage.php` | Language selection with reply/inline keyboards |
| `support.php` | Support ticket bot |

```bash
php examples/echo.php
php examples/commands-demo.php
php examples/retry-demo.php <chat_id>
```

---

## 🧰 Development

```bash
vendor/bin/phpunit       # run tests
vendor/bin/phpstan       # static analysis
vendor/bin/rector        # automated refactoring (add --dry-run to preview)
vendor/bin/phpcs         # code style
```

See [docs/21-testing.md](docs/21-testing.md) for how to test bots built on this framework.

---

## 📄 License

MIT License - feel free to use in your projects.

---

## 🔗 Resources

- [Telegram Bot API Documentation](https://core.telegram.org/bots/api)
- [@BotFather](https://t.me/BotFather) - Create and manage bots
- [Telegram Bots FAQ](https://core.telegram.org/bots/faq)

---

<div align="center">

**Built with ❤️ for the Telegram community**

</div>
