[← Documentation Home](README.md)

# Installation

## 1. Get the code

This framework has no Composer registry package yet — you install it by
cloning or copying the repository into your project.

```bash
git clone https://github.com/ahmcho/TelegramBot.git tg-bots
cd tg-bots
```

If you're adding this into an existing project, copy the `src/` directory
and `autoload.php` into your codebase, keeping the same relative structure.

## 2. Load the classes

You have two options, depending on whether your project already uses
Composer.

### Option A: the built-in autoloader (no Composer required)

Every framework file requires exactly one line:

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();
```

`autoload.php` is a small, dependency-free PSR-4 autoloader
(`autoload.php:12`) that maps the `AhmCho\Telegram\` namespace to `src/`,
plus the bundled `Psr\Log\` interface to `src/Psr/Log/`. This is genuinely
all it does — there's no magic and nothing else to configure.

### Option B: Composer's autoloader

The project ships a `composer.json` with a PSR-4 mapping:

```json
"autoload": {
    "psr-4": {
        "AhmCho\\Telegram\\": "src/"
    }
}
```

If your project already runs `composer dump-autoload`, requiring
`vendor/autoload.php` picks up the framework the same way any Composer
package would. Development dependencies (`phpunit`, `phpstan`,
`squizlabs/php_codesniffer`, `phpdocumentor/phpdocumentor`) are declared
under `require-dev` for contributors working *on* the framework — they are
not needed to *use* it.

## 3. Get a bot token

Every Telegram bot needs a token issued by
[@BotFather](https://t.me/BotFather) on Telegram:

1. Open a chat with `@BotFather`.
2. Send `/newbot` and follow the prompts (choose a name and a username
   ending in `bot`).
3. BotFather replies with a token that looks like
   `123456789:AAHn2sSp9nkOb2rc5tCFqADi0eKfxvHIzYs`. Copy it.

## 4. Configure your environment

Copy the example environment file and fill in your token:

```bash
cp .env.example .env
```

```dotenv
# .env
TELEGRAM_BOT_TOKEN=123456789:AAHn2sSp9nkOb2rc5tCFqADi0eKfxvHIzYs

# Optional — only for local dev against self-signed certs
# TELEGRAM_VERIFY_SSL=false

# Optional — required if you're deploying a webhook (see docs/11-webhooks.md)
# TELEGRAM_WEBHOOK_SECRET=some-long-random-string
```

`TelegramBot`'s constructor loads `.env` automatically via `EnvLoader` — you
don't need to call anything yourself (see
[Configuration](03-configuration.md) for exactly how and where it looks for
the file).

**Never commit your real `.env` file.** The repository's `.gitignore`
already excludes it; only `.env.example` (with placeholder values) is
tracked.

## 5. Verify it works

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();
$me = $bot->getMe();

echo "Bot username: @{$me['username']}\n";
```

Running this should print your bot's username. If it throws instead, jump
to [Error Handling](18-error-handling.md) to understand what the exception
is telling you (most commonly: an invalid token, or no network route to
`api.telegram.org`).

## Directory layout at a glance

```
tg-bots/
├── autoload.php        # PSR-4 autoloader (Option A above)
├── .env.example        # Copy to .env and fill in your token
├── src/                # The framework itself
├── public/webhook.php  # Production-ready webhook endpoint (see docs/11-webhooks.md)
├── examples/           # Runnable example scripts
└── tests/              # PHPUnit test suite
```

You will spend almost all of your time inside `src/Api/Methods/` (the
service classes) once you're past this setup stage — see
[The Bot Facade](05-the-bot-facade.md) for the map of what lives where.

---

[← Previous: Introduction](01-introduction.md) | [Documentation Home](README.md) | [Next: Configuration →](03-configuration.md)
