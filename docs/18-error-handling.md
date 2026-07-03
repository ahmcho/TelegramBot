[← Documentation Home](README.md)

# Error Handling

Every exception this framework throws extends a single abstract base,
`TelegramException` (`src/Exception/TelegramException.php`), which itself
extends PHP's built-in `Exception`. There are three concrete exception
types, and which one you get tells you exactly what layer of the stack
failed:

```
TelegramException (abstract)
├── ApiException          — Telegram's API itself returned an error (400, 403, 429, ...)
├── HttpClientException   — the HTTP layer failed before reaching a parsed API response (DNS, timeout, dropped connection, invalid JSON)
└── BulkSendException     — a bulk operation failed completely (all requests failed); carries the full BulkResult
```

## Catching each type

```php
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;
use AhmCho\Telegram\Bulk\BulkSendException;

try {
    $result = $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Hello']);
} catch (ApiException $e) {
    // Telegram rejected the request — inspect why
    echo $e->getErrorCode();      // ?int — Telegram's error_code, e.g. 400
    echo $e->getHttpCode();       // ?int — the HTTP status code, e.g. 400
    print_r($e->getResponseBody()); // full decoded response body as an array
} catch (HttpClientException $e) {
    // Never reached Telegram, or got back something that wasn't parseable JSON
    echo $e->getHttpCode();       // ?int — may be null for pure transport failures
    echo $e->getResponseBody();   // ?string — raw response body, if any was received
} catch (BulkSendException $e) {
    // A bulk operation failed completely (every recipient failed)
    $result = $e->getResult();    // the full BulkResult — see docs/16-bulk-operations.md
}
```

Order your `catch` blocks from most specific to least specific if you
need different handling per type — all three ultimately also match a
`catch (\AhmCho\Telegram\Exception\TelegramException $e)` if you want one
fallback branch for "any error this framework raises."

## `ApiException`: Telegram said no

Thrown whenever Telegram's response has `"ok": false` — chat not found, bot
was blocked, missing required parameter, insufficient permissions, rate
limited, and so on. The framework parses Telegram's error response and
gives you structured access to it rather than a bare string:

```php
try {
    $bot->messages()->send(['chat_id' => $blockedUserId, 'text' => 'Hi']);
} catch (ApiException $e) {
    if ($e->getErrorCode() === 403) {
        // User has blocked the bot — mark them inactive in your own database
        markUserInactive($blockedUserId);
    } else {
        error_log("Unexpected API error {$e->getErrorCode()}: {$e->getMessage()}");
    }
}
```

## `HttpClientException`: the request never got a valid answer

Thrown for genuine transport failures: DNS resolution failed, the
connection timed out, the connection was dropped mid-request, or the
response body wasn't valid JSON at all (as opposed to valid JSON with
`"ok": false`, which is an `ApiException`). If you're using
[Retry & Resilience](17-retry-and-resilience.md), every `HttpClientException`
that reaches your code has already exhausted its retries — it represents
a persistent, not transient, transport problem.

## `BulkSendException`: everything in the batch failed

As covered in [Bulk Operations & Broadcasting](16-bulk-operations.md),
this is thrown only when **every** request in a bulk operation failed —
not for ordinary partial failure (some users blocked the bot, most
didn't), which returns a `BulkResult` without throwing. `getResult()`
gives you the same `BulkResult` you'd have gotten on success, so you can
inspect per-request errors even in the failure path.

## Turning off exceptions entirely

If you'd rather check return values than catch exceptions,
`BotConfig::$throwExceptions` (default `true`) can be set to `false`:

```php
$config = new BotConfig(token: $token, throwExceptions: false);
```

**Be aware of what this actually changes**: it's read by
`BulkOperationManager` to decide whether to throw `BulkSendException` on
total bulk failure (`src/Bulk/BulkOperationManager.php:77`). It does not
change how `ApiException` or `HttpClientException` propagate from
individual (non-bulk) API calls — those are always thrown, since there is
no sensible non-exception "return value" for a failed `send()` call that
was supposed to return a `Message` object. If you need non-throwing
single-call error handling, wrap the call in your own `try/catch` rather
than relying on this flag.

## What gets logged automatically

If logging is enabled (see [Logging](19-logging.md)), `ApiService::call()`
logs every outgoing request at `DEBUG` level and every exception at
`ERROR` level automatically, with the bot token stripped from logged
params (`ApiService::sanitizeParams()`, `src/Api/ApiService.php:74-79`) —
you don't need to manually log API failures for basic observability, just
configure a logger and read the log file.

---

[← Previous: Retry & Resilience](17-retry-and-resilience.md) | [Documentation Home](README.md) | [Next: Logging →](19-logging.md)
