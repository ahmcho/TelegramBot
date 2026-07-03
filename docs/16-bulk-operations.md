[← Documentation Home](README.md)

# Bulk Operations & Broadcasting

Sending the same or different messages to hundreds or thousands of chats
one at a time would take far too long — at even 100ms per request, 10,000
recipients would take over 16 minutes. `BulkOperationManager`
(`src/Bulk/BulkOperationManager.php`) sends requests **in parallel** using
PHP's `curl_multi_exec` (via `CurlHttpClient`), or serially with a warning
if the stream fallback client is in use (see [HTTP Clients](20-http-clients.md)).

You don't call `BulkOperationManager` directly — you reach it through a
service's `sendBulk()` / `broadcast()` methods, currently exposed on
`MessageService` (see [Sending Messages](06-sending-messages.md)).

## `sendBulk()`: different content per recipient

```php
$result = $bot->messages()->sendBulk([
    ['chat_id' => 111, 'text' => 'Hi Alice, your order shipped!'],
    ['chat_id' => 222, 'text' => 'Hi Bob, your order shipped!'],
    ['chat_id' => 333, 'text' => 'Hi Carol, your order shipped!'],
]);
```

Each element is a full parameter array, exactly like what you'd pass to
`send()` individually — different chat IDs, different text, different
`parse_mode`, whatever you need per-recipient.

## `broadcast()`: the same content to many chats

```php
$result = $bot->messages()->broadcast(
    chatIds: [111, 222, 333],
    text: 'Scheduled maintenance tonight at 10pm UTC.',
    commonParams: ['parse_mode' => 'MarkdownV2'], // optional, merged into every message
);
```

Internally this just builds a `sendBulk()`-shaped array by merging
`chat_id` into `$commonParams` for every recipient
(`src/Bulk/BulkOperationManager.php:94-117`) — it's a convenience wrapper,
not a different code path.

## `BulkResult`: what you get back

Both methods return a `BulkResult` (`src/Bulk/BulkResult.php`), an
immutable, `Countable` value object:

```php
$result->total;              // int — total requests attempted
$result->successful;         // int
$result->failed;             // int
$result->results;             // array<int, array{success, chat_id, message_id, data, error}> — one per request
$result->errors;              // array<string> — error messages for failed requests only

$result->isSuccess(): bool;         // true if failed === 0
$result->hasFailures(): bool;       // true if failed > 0
$result->getSuccessRate(): float;   // percentage, 0.0 if total === 0
$result->getFailedResults(): array;
$result->getSuccessfulResults(): array;
count($result);                     // same as $result->total, via Countable
```

```php
$result = $bot->messages()->broadcast($allUserIds, 'New feature announcement!');

echo "{$result->successful}/{$result->total} delivered ({$result->getSuccessRate()}%)\n";

foreach ($result->getFailedResults() as $failure) {
    error_log("Failed to reach chat {$failure['chat_id']}: {$failure['error']}");
}
```

## Partial failure vs. total failure

Sending to 1,000 users where 5 have blocked the bot is **normal** — those
5 fail, the other 995 succeed, and you get a `BulkResult` with
`failed === 5` to inspect. This does **not** throw an exception, even with
the default `throwExceptions: true` config.

An exception is only thrown when **every single request in the batch
fails** (`$result->successful === 0`) — that's a signal something is
fundamentally broken (wrong token, no network, wrong API URL), not that a
few users individually blocked the bot:

```php
use AhmCho\Telegram\Bulk\BulkSendException;

try {
    $result = $bot->messages()->broadcast($chatIds, 'Announcement');
} catch (BulkSendException $e) {
    // Every single request failed — this is not "some users blocked the bot"
    $result = $e->getResult(); // the full BulkResult is still available for inspection
    error_log("Broadcast failed completely: {$e->getMessage()}");
}
```

If you'd rather never have bulk operations throw and always inspect the
`BulkResult` yourself, set `throwExceptions: false` on your `BotConfig`
(see [Configuration](03-configuration.md)) — partial failures already
never throw either way, so this only changes the "all requests failed"
case.

## Tuning concurrency

```php
$result = $bot->messages()->broadcast(
    $chatIds,
    'Announcement',
    commonParams: [],
    options: [
        'max_concurrent' => 50, // default: 30
        'delay_ms' => 20,       // default: 0 — pause between batches, useful to stay under rate limits
    ]
);
```

The same `options` array works identically on `sendBulk()`. Telegram
applies its own rate limits per bot and per chat regardless of how you
tune this — see [Retry & Resilience](17-retry-and-resilience.md) for how
429 responses are handled automatically when combined with the retry
layer.

## What counts as "bulk-capable"

`ApiMethod::isBulkCapable(): bool` (`src/Enums/ApiMethod.php:124-136`) is
metadata marking which Telegram methods are conceptually suited to bulk
sending: `SEND_MESSAGE`, `SEND_PHOTO`, `SEND_DOCUMENT`, `SEND_VIDEO`,
`SEND_AUDIO`, `SEND_VOICE`, `SEND_ANIMATION`, `COPY_MESSAGE`. As of this
writing, only `SEND_MESSAGE` is actually wired up to a `sendBulk()` /
`broadcast()` method (on `MessageService`) — the flag on the others is
forward-looking metadata rather than a guarantee that a bulk method exists
for them today. If you need bulk photo/document sending, you can call
`BulkOperationManager::sendBulk(ApiMethod::SEND_PHOTO, [...])` directly via
`$bot->api()->getBulkManager()`, following the same shape
`MessageService::sendBulk()` uses internally.

---

[← Previous: Games & Payments](15-games-and-payments.md) | [Documentation Home](README.md) | [Next: Retry & Resilience →](17-retry-and-resilience.md)
