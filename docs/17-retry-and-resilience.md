[← Documentation Home](README.md)

# Retry & Resilience

Two things go wrong with any HTTP integration: the network itself hiccups
(a dropped connection, a DNS blip, a timeout), or the remote API tells you
to slow down (rate limiting). `TelegramBot` has a built-in retry layer
(`src/Bot/TelegramBot.php:278-382`) that handles both, with exponential
backoff.

## The generic retry wrapper

```php
$result = $bot->executeWithRetry(
    callback: fn() => $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Hello']),
    options: [
        'max_retries' => 3,        // default
        'initial_delay_ms' => 1000, // default
        'max_delay_ms' => 10000,    // default
        'on_retry' => function (int $attempt, \Throwable $e, int $delayMs) {
            error_log("Retry attempt {$attempt} after {$e->getMessage()}, waiting {$delayMs}ms");
        },
    ]
);
```

`executeWithRetry(callable $callback, array $options = []): mixed` wraps
**any** callable, not just message sending — use it around any operation
you want retried on transient failure.

## Retry behavior, precisely

- **`ApiException` (Telegram returned an error response):**
  - HTTP 4xx errors **other than 429** are never retried — these mean
    "this specific request is wrong" (bad chat ID, missing required
    field, etc.), and retrying an identical malformed request would just
    fail identically every time.
  - HTTP 429 (rate limited) **is** retried, and the delay is set from
    Telegram's own `retry_after` hint in the response body
    (`$response['parameters']['retry_after']`) rather than the generic
    exponential backoff — the framework waits exactly as long as Telegram
    tells it to.
  - HTTP 5xx errors are retried with exponential backoff.
- **`HttpClientException` (network/transport failure — timeout, DNS
  failure, dropped connection):** always retried with exponential
  backoff. Since `ApiException` and `HttpClientException` are kept
  strictly separate (an `HttpClientException` is never thrown for a
  parsed Telegram error response — see
  [Error Handling](18-error-handling.md)), every `HttpClientException`
  that reaches this layer is, by construction, a transient transport
  failure — there's no non-transient subset to special-case.
- **Backoff:** starts at `initial_delay_ms`, doubles after each attempt,
  capped at `max_delay_ms`. With the defaults (1000ms initial, 10000ms
  max, 3 retries), the delays are roughly 1s, 2s, 4s before giving up.
- **Exhaustion:** after `max_retries` attempts, the last exception is
  re-thrown to your calling code — `executeWithRetry()` never silently
  swallows a persistent failure.

## Message-specific and bulk-specific shortcuts

```php
$bot->sendMessageWithRetry(array $params, array $options = []): array;
// Equivalent to: $bot->executeWithRetry(fn() => $bot->messages()->send($params), $options)

$bot->sendBulkWithRetry(array $messages, array $bulkOptions = [], array $retryOptions = []): mixed;
// Equivalent to: $bot->executeWithRetry(fn() => $bot->messages()->sendBulk($messages, $bulkOptions), $retryOptions)
```

Note that `sendBulkWithRetry()` retries the **entire bulk operation** as a
unit on total failure (bulk operations already handle partial failure
gracefully without throwing — see
[Bulk Operations & Broadcasting](16-bulk-operations.md)) — it does not
retry individual failed recipients within an otherwise-successful batch.

## Choosing whether to use retry at all

Not every operation should be retried blindly. A few situations where you
should think twice:

- **Non-idempotent operations.** Retrying `sendMessage` after a timeout
  risks sending the message twice if the first request actually succeeded
  server-side but the response was lost in transit. For most bots this is
  an acceptable, rare tradeoff — but if double-sends would be genuinely
  harmful (e.g. sending a one-time payment confirmation), consider
  designing for idempotency on your own side (e.g. checking whether the
  action already happened before retrying) rather than relying purely on
  this layer.
- **User-facing latency.** Three retries with exponential backoff can add
  several seconds of wall-clock time before the caller gets a final
  answer (success or the re-thrown exception). For a synchronous request
  handler where the user is waiting on the response, weigh this against
  just failing fast and letting the user retry manually.

## Example: retrying a webhook handler's outbound reply

```php
$bot->processWebhook(function (array $update) use ($bot) {
    if (!isset($update['message']['text'])) {
        return;
    }

    $chatId = $update['message']['chat']['id'];

    $bot->sendMessageWithRetry([
        'chat_id' => $chatId,
        'text' => 'Processing your request...',
    ], [
        'max_retries' => 2,
        'initial_delay_ms' => 500,
    ]);
});
```

---

[← Previous: Bulk Operations & Broadcasting](16-bulk-operations.md) | [Documentation Home](README.md) | [Next: Error Handling →](18-error-handling.md)
