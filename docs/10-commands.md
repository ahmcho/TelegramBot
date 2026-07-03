[← Documentation Home](README.md)

# Commands

`CommandHandler` (`src/Command/CommandHandler.php`), reached via
`$bot->commands()`, is a built-in router for `/command` messages. Instead
of writing a chain of `if ($text === '/start')` checks, you register
handlers once and let the router dispatch to them.

## Registering commands

```php
$bot->commands()
    ->register('start', function (TelegramBot $bot, int $chatId, array $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome!']);
    }, 'Start the bot')
    ->register('help', function (TelegramBot $bot, int $chatId, array $args) {
        $bot->commands()->sendHelp($chatId);
    }, 'Show this help message');
```

`register(string $command, callable $callback, string $description = '')`
returns `$this`, so calls chain fluently. Command names are normalized on
both registration and lookup — leading `/` is stripped and the name is
lowercased (`CommandHandler::normalizeCommand()`,
`src/Command/CommandHandler.php:288-291`), so `register('Start', ...)`,
`register('/start', ...)`, and an incoming `/START` message all resolve to
the same handler.

**Callback signature:**
`function(TelegramBot $bot, int $chatId, array $args): void`. `$args` is
everything after the command name, split on spaces — sending `/echo hello
world` gives `$args = ['hello', 'world']`.

### Registering many commands at once

```php
$bot->commands()->registerCommands([
    'start' => fn($bot, $chatId, $args) => $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Hi!']),
    'help' => [
        'callback' => fn($bot, $chatId, $args) => $bot->commands()->sendHelp($chatId),
        'description' => 'Show help',
    ],
]);
```

Each entry is either a bare callable, or an array with `callback` and
optionally `description`.

## Dispatching updates

```php
$bot->processWebhook(function (array $update) use ($bot) {
    $bot->commands()->handleUpdate($update);
});
```

`handleUpdate(array $update): bool` returns `true` if it dispatched a
command (or ran the default callback), `false` if the update wasn't a
command message at all (no `message`, or `text` doesn't start with `/`).
This lets you chain other handling after it:

```php
$bot->processWebhook(function (array $update) use ($bot) {
    if ($bot->commands()->handleUpdate($update)) {
        return; // a command handled it
    }

    // fall through to your own non-command logic (free text, photos, callback queries, ...)
});
```

## Auto-generated help

```php
$bot->commands()->register('help', function (TelegramBot $bot, int $chatId, array $args) {
    $bot->commands()->sendHelp($chatId);
}, 'Show this help message');
```

`sendHelp(int $chatId): void` sends a MarkdownV2-formatted message listing
every registered command's description (`generateHelp()` builds the text;
`sendHelp()` sends it with `parse_mode` set to `ParseMode::MARKDOWN_V2->value`).
Commands registered with an empty description string are dispatchable but
don't appear in this generated list.

## Middleware

Middleware runs before every command dispatch — useful for things like
rate limiting, auth checks, or logging:

```php
$bot->commands()->addMiddleware('require_registration', function (TelegramBot $bot, int $chatId, string $command, array $args) {
    if (!userIsRegistered($chatId)) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Please /register first.']);
        return false; // halts — the command itself will not run
    }

    return true; // continue to the next middleware / the command
});
```

**Signature:**
`function(TelegramBot $bot, int $chatId, string $command, array $args): bool`.
Returning `false` from any middleware stops the chain entirely — the
matched command's callback (and any middleware registered after it) never
runs, but `handleUpdate()` still returns `true` (the update *was* handled,
just short-circuited).

Middleware runs in registration order, for **every** command dispatch
(there's no per-command middleware scoping) — if you need conditional
logic, put the condition inside the middleware itself.

If a middleware callback throws, the error is logged via `error_log()` and
execution continues to the next middleware — a broken middleware doesn't
take down the rest of your bot (`src/Command/CommandHandler.php:146-157`).

## Unknown commands

```php
$bot->commands()->setDefault(function (TelegramBot $bot, int $chatId, string $command, array $args) {
    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => "Unknown command: /{$command}. Try /help.",
    ]);
});
```

**Note the signature difference from regular command callbacks:** the
default callback receives `$command` as its third argument (so you know
*which* unrecognized command was sent), while a normal registered
command's callback does not, since it already knows its own name.

If a default callback throws, the error is logged but — unlike a matched
command's callback — no generic error message is sent to the user
(`src/Command/CommandHandler.php:183-190`); consider wrapping risky logic
in your own `try/catch` inside the default callback if you want user
feedback on failure there too.

## Error handling inside command callbacks

If a **matched command's** callback throws any `\Throwable`, `CommandHandler`
catches it, sends the user a generic
`"An error occurred. Please try again."` message, and logs the full
exception (class, message, file, line, stack trace) via `error_log()`
(`src/Command/CommandHandler.php:160-179`). Your callback never needs its
own top-level `try/catch` purely for "don't crash the bot" purposes — that
safety net already exists. You'd only add your own `try/catch` if you want
different user-facing behavior for a specific expected failure.

## Introspection and management

```php
$bot->commands()->getRegisteredCommands(): array;  // ['start', 'help', ...]
$bot->commands()->hasCommand('start'): bool;
$bot->commands()->unregister('start'): bool;        // true if it existed and was removed
$bot->commands()->clear(): void;                     // wipes commands, descriptions, AND middleware
```

## Putting it together

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

$bot->commands()
    ->register('start', function ($bot, $chatId, $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome! Try /help.']);
    }, 'Start the bot')
    ->register('echo', function ($bot, $chatId, $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => implode(' ', $args) ?: '(nothing to echo)']);
    }, 'Echo back your text')
    ->register('help', function ($bot, $chatId, $args) {
        $bot->commands()->sendHelp($chatId);
    }, 'Show this help message')
    ->setDefault(function ($bot, $chatId, $command, $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => "Unknown command: /{$command}"]);
    });

$bot->processWebhook(function (array $update) use ($bot) {
    $bot->commands()->handleUpdate($update);
});
```

---

[← Previous: Media & Files](09-media-and-files.md) | [Documentation Home](README.md) | [Next: Webhooks →](11-webhooks.md)
