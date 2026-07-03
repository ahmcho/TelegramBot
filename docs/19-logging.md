[← Documentation Home](README.md)

# Logging

Logging is optional, PSR-3 compliant, file-based, and — when disabled —
has genuinely zero runtime cost (every internal call site checks for
`null` before doing any work; see below).

## Enabling and configuring it

Logging is controlled entirely through `BotConfig` (see
[Configuration](03-configuration.md)):

```php
$config = new BotConfig(
    token: $token,
    loggingEnabled: true,   // default
    logFilePath: 'bot.log', // default — relative paths resolve against the current working directory
    logLevel: 'INFO',       // default — DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY
    logMaxBytes: 0,         // default — 0 disables rotation
    logTimezone: 'UTC'      // default
);

$bot = new TelegramBot(null, $config);
$logger = $bot->getLogger(); // returns null if loggingEnabled is false
```

## Getting the logger and writing your own entries

```php
$logger = $bot->getLogger();

if ($logger !== null) {
    $logger->info('Processing order {order_id} for user {user_id}', [
        'order_id' => $orderId,
        'user_id' => $userId,
    ]);
}
```

Every framework class that logs follows this same
`if ($logger !== null)` pattern (via `LoggerHelperTrait`,
`src/Logging/Traits/LoggerHelperTrait.php`) — logging is designed to
**never throw** and never slow down the hot path when disabled. Write your
own logging code the same way.

### PSR-3 message interpolation

`Logger::log()` supports PSR-3-style placeholder interpolation
(`src/Logging/Logger.php:130-152`) — `{key}` in your message is replaced
with the matching key from the context array, for any scalar value or
object with a `__toString()` method:

```php
$logger->warning('Rate limited: retry after {seconds}s', ['seconds' => 30]);
// Logged as: Rate limited: retry after 30s
```

Context values that aren't scalar or stringable are skipped from
interpolation but still appended to the entry as pretty-printed JSON under
a `Context:` line.

## Log levels

`LogLevel` (`src/Logging/LogLevel.php`) implements the full PSR-3 set,
in RFC 5424 severity order:

| Level | Weight |
|---|---|
| `DEBUG` | 100 |
| `INFO` | 200 |
| `NOTICE` | 250 |
| `WARNING` | 300 |
| `ERROR` | 400 |
| `CRITICAL` | 500 |
| `ALERT` | 550 |
| `EMERGENCY` | 600 |

`logLevel` in your config is the **minimum** level that gets written — set
it to `'WARNING'` in production to silence `DEBUG`/`INFO`/`NOTICE` noise
while still capturing anything that matters. Each PSR-3 method
(`$logger->debug()`, `->info()`, `->notice()`, `->warning()`, `->error()`,
`->critical()`, `->alert()`, `->emergency()`) maps to its own distinct
level — none of them are silently collapsed into a coarser one.

## Logging exceptions

```php
try {
    // ...
} catch (\Throwable $e) {
    $bot->getLogger()?->logException($e, ['extra' => 'context']);
}
```

`logException()` builds a rich context object via `ExceptionContext`
(`src/Logging/Context/ExceptionContext.php`) that includes the exception
class, message, code, file, line, and full stack trace, plus
exception-type-specific fields:

- `ApiException` → adds `error_code`, `http_code`
- `HttpClientException` → adds `http_code`, `response_body`
- `BulkSendException` → adds `bulk_total`, `bulk_successful`,
  `bulk_failed`, `success_rate`

This is what `ApiService` uses internally to log every failed API call at
`ERROR` level automatically — see [Error Handling](18-error-handling.md).

## Log format and timestamps

```
[2026-07-03 14:22:01] [ERROR] Chat not found
Context: {
    "error_code": 400,
    "http_code": 400
}
```

Timestamps default to **UTC** regardless of the server's PHP `date.timezone`
setting, so logs from multiple servers (or a server whose timezone config
changes) stay comparable. Override with `logTimezone` in your config using
any valid IANA timezone name (`'America/New_York'`, `'Europe/London'`,
etc.) if you specifically want local server time instead.

## Log rotation

Set `logMaxBytes` to enable single-generation rotation — when the log file
exceeds this size, it's renamed to `<path>.1` (overwriting any previous
`.1` file) and a fresh file is started:

```php
$config = new BotConfig(token: $token, logMaxBytes: 10 * 1024 * 1024); // rotate at 10 MB
```

This is intentionally simple (one generation, not a numbered sequence like
`log.1`, `log.2`, ...) — for anything more sophisticated (compressed
archives, retention policies, shipping to a log aggregator), point
`logFilePath` at a location your OS's own log rotation tool (`logrotate`,
etc.) already manages, and disable this framework's rotation (leave
`logMaxBytes` at its default `0`).

## Concurrency safety

`FileLogHandler` (`src/Logging/FileLogHandler.php`) writes with an
exclusive, non-blocking `flock()` and retries up to 3 times with
exponential backoff if the lock is briefly held by another process
(`writeWithLock()`, `FileLogHandler.php:78-104`) — safe for multiple PHP
workers (e.g. PHP-FPM handling concurrent webhook requests) writing to the
same log file.

## Reading recent log entries

```php
$handler = new \AhmCho\Telegram\Logging\FileLogHandler('bot.log');
$lastHundredLines = $handler->readLastLines(100);
```

## Disabling logging entirely

```php
$config = new BotConfig(token: $token, loggingEnabled: false);
$bot = new TelegramBot(null, $config);

$bot->getLogger(); // null — no file is ever created, no log calls do any work
```

Useful for tests (see [Testing](21-testing.md)) or any environment where
you have your own external logging/observability pipeline and don't want
this framework writing its own file.

---

[← Previous: Error Handling](18-error-handling.md) | [Documentation Home](README.md) | [Next: HTTP Clients →](20-http-clients.md)
