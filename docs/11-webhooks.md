[← Documentation Home](README.md)

# Webhooks

Webhooks are how Telegram delivers updates in production: instead of your
bot repeatedly asking "anything new?" (long polling), Telegram POSTs each
update directly to a URL you host. This page covers `WebhookService` (for
registering the webhook with Telegram), the `TelegramBot` webhook helpers
(for receiving and parsing updates), and the bundled production endpoint
at `public/webhook.php`.

## Registering your webhook URL

```php
$bot->webhooks()->set([
    'url' => 'https://your-domain.com/webhook.php',
    'secret_token' => 'a-long-random-string-you-choose',
]);
```

Telegram requires HTTPS — plain HTTP webhook URLs are rejected. `set()`
maps to Telegram's [`setWebhook`](https://core.telegram.org/bots/api#setwebhook)
and accepts all of its parameters (`certificate` for self-signed certs,
`max_connections`, `allowed_updates`, `drop_pending_updates`, etc.), not
just the two shown above.

```php
$bot->webhooks()->getInfo(): array;   // getWebhookInfo — current URL, pending update count, last error, etc.
$bot->webhooks()->delete(array $params = []): mixed; // deleteWebhook — pass ['drop_pending_updates' => true] to also clear the queue
```

Switching from webhook mode back to long polling requires calling
`delete()` first — Telegram won't deliver to `getUpdates()` while a
webhook is set.

## Why `secret_token` matters

Anyone who discovers your webhook URL can POST arbitrary JSON to it and
your bot will process it as if Telegram sent it — there's no other
built-in authentication on the endpoint itself. The `secret_token` you
pass to `set()` is echoed back by Telegram on every real update as the
`X-Telegram-Bot-Api-Secret-Token` HTTP header. Your endpoint checks that
header against the value you configured and rejects anything that doesn't
match. This is the primary defense against forged updates being injected
into your bot — treat it the same way you'd treat a webhook signing
secret for any other provider (Stripe, GitHub, etc.).

## Receiving updates: the `TelegramBot` helpers

```php
$bot->getWebhookUpdates(): ?array   // reads and JSON-decodes php://input; null if empty or invalid JSON
$bot->processWebhook(callable $handler): void  // calls $handler($update) only if an update was actually received
```

The common pattern:

```php
<?php
require_once __DIR__ . '/autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

$bot->processWebhook(function (array $update) use ($bot): void {
    if (isset($update['message']['text'])) {
        $chatId = $update['message']['chat']['id'];
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Got it!']);
    }
});
```

`processWebhook()` does nothing (silently) if `php://input` was empty or
not valid JSON — there's no update to react to, which is the normal case
for health-check pings or accidental GET requests hitting your endpoint.

### Overriding the input source (for testing)

```php
$bot->setInputSource('php://memory'); // or any readable stream path
```

This exists specifically so tests can feed a fake update body without a
real HTTP request — see [Testing](21-testing.md) for how `WebhookStreamWrapper`
uses this seam.

## The production endpoint: `public/webhook.php`

The repository ships a ready-to-deploy webhook script at
`public/webhook.php`. Point Telegram's `setWebhook` URL at wherever you
host this file. What it does, end to end:

1. Rejects any non-`POST` request with HTTP 405.
2. Constructs a `TelegramBot()` (loading `.env` automatically).
3. **Validates the secret token** — reads `TELEGRAM_WEBHOOK_SECRET` from
   the environment and compares it against the incoming
   `X-Telegram-Bot-Api-Secret-Token` header using `hash_equals()` (a
   timing-attack-safe comparison). Returns HTTP 403 immediately on
   mismatch. If `TELEGRAM_WEBHOOK_SECRET` isn't configured at all, it logs
   a warning via `error_log()` and processes the request anyway
   (backward-compatible, but you should always set this in production).
4. Calls `$bot->processWebhook(...)` with a `handleUpdate()` function that
   demonstrates handling `/start`, `/help`, `/echo`, `/info`, plain text,
   photos, stickers, voice messages, and callback queries.
5. Returns HTTP 200 with body `OK` on success.
6. On any uncaught error: logs the real exception internally (never to
   the response), and returns a generic HTTP 500 JSON body
   `{"error": "Internal server error"}` — the caller never sees exception
   messages, stack traces, or file paths.

Deploy it, set `TELEGRAM_WEBHOOK_SECRET` in your production `.env`, run
`$bot->webhooks()->set([...])` once, and you're live. Treat
`public/webhook.php`'s example command handlers as a template to replace
with your own logic — or route everything through
[`CommandHandler`](10-commands.md) instead, which is the more scalable
approach for anything beyond a handful of commands.

### A note on error handling in your own webhook code

Every `catch` block in `public/webhook.php` catches `\Throwable`, not just
`\Exception` — this matters because a `TypeError` from malformed input (a
missing array key that should have existed, a wrong type) is a native PHP
`Error`, not an `Exception`, and would otherwise bypass every catch block
and fall through as an uncaught fatal with no proper HTTP 500 response. If
you write your own webhook handler from scratch, catch `\Throwable`, not
`\Exception`, for the same reason — see
[Error Handling](18-error-handling.md) for the full exception hierarchy
this framework itself throws.

## Long polling vs. webhooks: when to use which

| | Long polling (`getUpdates()`) | Webhooks (`public/webhook.php`) |
|---|---|---|
| Needs a public HTTPS URL | No | Yes |
| Good for local development | Yes | Only with a tunnel (ngrok, etc.) |
| Good for production | No (ties up a process) | Yes |
| Update latency | Depends on polling interval | Near-instant |
| Setup complexity | None | One-time `setWebhook` call + hosting |

---

[← Previous: Commands](10-commands.md) | [Documentation Home](README.md) | [Next: Chats & Administration →](12-chats-and-administration.md)
