[← Documentation Home](README.md)

# Quickstart

This page assumes you've already installed the framework and have a
`.env` file with `TELEGRAM_BOT_TOKEN` set (see [Installation](02-installation.md)).
There are two ways to receive updates from Telegram — **long polling**
(your script asks Telegram "any new messages?" in a loop) and **webhooks**
(Telegram pushes updates to a URL you host). This page shows both.

## Option 1: Long polling (easiest to run locally)

No public URL needed — just run a PHP script:

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

$offset = 0;

while (true) {
    $updates = $bot->getUpdates(['offset' => $offset, 'timeout' => 30]);

    foreach ($updates as $update) {
        $offset = $update['update_id'] + 1;

        if (isset($update['message']['text'])) {
            $chatId = $update['message']['chat']['id'];
            $text = $update['message']['text'];

            $bot->messages()->send([
                'chat_id' => $chatId,
                'text' => "You said: {$text}",
            ]);
        }
    }
}
```

Run it with `php bot.php`. Message your bot on Telegram and it will echo
back whatever you send. `offset` is how Telegram's long-polling protocol
avoids redelivering updates you've already seen — always advance it past
the highest `update_id` you've processed.

This is the fastest way to experiment, but it's not suitable for
production (it ties up a long-running PHP process). For anything you
deploy, use webhooks instead.

## Option 2: Webhooks (production)

With webhooks, Telegram POSTs each update to a URL you control. This
framework ships a ready-to-deploy endpoint at `public/webhook.php`, and
covers the setup, security, and internals fully in
[Webhooks](11-webhooks.md). The minimal version looks like this:

```php
<?php
// public/webhook.php
require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

$bot->processWebhook(function (array $update) use ($bot): void {
    if (isset($update['message']['text'])) {
        $chatId = $update['message']['chat']['id'];
        $text = $update['message']['text'];

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => "You said: {$text}",
        ]);
    }
});
```

Then tell Telegram where to send updates (one-time setup, run from any PHP
script or the CLI):

```php
$bot->webhooks()->set([
    'url' => 'https://your-domain.com/webhook.php',
    'secret_token' => 'the-same-value-as-TELEGRAM_WEBHOOK_SECRET-in-.env',
]);
```

Deploy `public/webhook.php` behind HTTPS (required by Telegram) and you're
live. Full detail — including *why* the secret token matters and how the
bundled endpoint validates it — is in [Webhooks](11-webhooks.md).

## Adding a keyboard

Most bots aren't pure text — here's the echo bot again, this time with an
inline keyboard attached:

```php
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;

$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::callback('👍 Like', 'like'),
        Button::callback('👎 Dislike', 'dislike')
    )
    ->build();

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'What do you think?',
    'reply_markup' => $keyboard,
]);
```

See [Keyboards](08-keyboards.md) for the full builder API, including reply
keyboards and handling the callback query when a button is tapped.

## Routing commands instead of `if` chains

Once your bot needs to handle `/start`, `/help`, and a few other commands,
reach for the built-in `CommandHandler` instead of writing
`if ($text === '/start')` chains:

```php
$bot->commands()
    ->register('start', function (TelegramBot $bot, int $chatId, array $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome!']);
    }, 'Start the bot')
    ->register('help', function (TelegramBot $bot, int $chatId, array $args) {
        $bot->commands()->sendHelp($chatId); // auto-generates a formatted list
    }, 'Show this help message');

$bot->processWebhook(function (array $update) use ($bot) {
    $bot->commands()->handleUpdate($update);
});
```

Full detail, including middleware and unknown-command fallbacks, in
[Commands](10-commands.md).

## What's next

You now have a working bot. From here:

- [The Bot Facade](05-the-bot-facade.md) explains what `$bot->messages()`,
  `$bot->media()`, etc. actually are, and how they're wired together.
- [Sending Messages](06-sending-messages.md) covers every message
  operation (editing, deleting, forwarding, bulk sending).
- [Error Handling](18-error-handling.md) explains what happens when
  `send()` fails and how to react to it correctly.

---

[← Previous: Configuration](03-configuration.md) | [Documentation Home](README.md) | [Next: The Bot Facade →](05-the-bot-facade.md)
