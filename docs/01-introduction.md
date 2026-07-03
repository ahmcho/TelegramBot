[← Documentation Home](README.md)

# Introduction

## What is AhmCho\Telegram?

AhmCho\Telegram is a PHP 8.1+ framework for building Telegram bots. It wraps
the [Telegram Bot API](https://core.telegram.org/bots/api) in a clean,
object-oriented, service-oriented interface, so you write:

```php
$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Hello, world!',
]);
```

instead of hand-rolling `curl_init()` calls and JSON encoding for every
single API method.

It has **zero external dependencies** — no Composer packages to pull in, no
vendor lock-in. It runs anywhere PHP 8.1+ with either the `curl` or
`openssl` extension runs.

## Why does this exist?

Most PHP Telegram bot libraries fall into one of two camps:

- **Thin wrappers** that are just `file_get_contents()` around a URL, with
  no structure, no error handling, and no help formatting messages safely.
- **Heavy frameworks** that pull in a dozen Composer dependencies for what
  is fundamentally a handful of HTTP calls.

AhmCho\Telegram sits in between: it gives you real structure — services,
typed enums, a proper exception hierarchy, retry logic, bulk sending,
logging — without asking you to install anything beyond PHP itself.

## What problems does it solve for you?

- **MarkdownV2 escaping.** Telegram's MarkdownV2 format requires escaping
  18 special characters or the API rejects your message. This framework
  escapes automatically whenever you set `parse_mode => 'MarkdownV2'` — see
  [Formatting Text](07-formatting-text.md).
- **Distinguishing API errors from network errors.** A "chat not found"
  response from Telegram and a DNS timeout are very different failures.
  This framework throws different exception types for each so your
  `catch` blocks can react correctly — see [Error Handling](18-error-handling.md).
- **Sending to many chats without blocking.** Broadcasting a message to
  10,000 users one at a time would take forever. The bulk operations layer
  sends requests in parallel via `curl_multi_exec` — see
  [Bulk Operations & Broadcasting](16-bulk-operations.md).
- **Transient failures.** Networks drop, Telegram rate-limits you. The
  retry layer handles both with exponential backoff, honoring Telegram's
  `retry_after` hint on HTTP 429 — see [Retry & Resilience](17-retry-and-resilience.md).
- **Command routing.** Instead of writing a chain of `if ($text === '/start')`,
  register commands with a router that handles argument parsing, middleware,
  and unknown-command fallbacks — see [Commands](10-commands.md).
- **Building keyboards without hand-writing JSON.** Fluent builders for
  both inline keyboards and reply keyboards — see [Keyboards](08-keyboards.md).

## Design philosophy

The codebase follows a few consistent rules, and understanding them will
help you navigate it:

- **Facade pattern.** You almost never touch internals directly. You
  create one `TelegramBot` object and call methods like `$bot->messages()`,
  `$bot->media()`, `$bot->chats()` to reach the functionality you need. See
  [The Bot Facade](05-the-bot-facade.md).
- **Service-oriented.** Each Telegram API domain (messages, media, chats,
  polls, webhooks, ...) has its own dedicated service class. If you're
  looking for `sendPoll`, you know to look in `PollsService`, not a
  2,000-line God object.
- **Immutable configuration.** `BotConfig` never mutates in place — every
  `with*()` method returns a *new* config object. This avoids a whole class
  of bugs where config changes leak between requests.
- **Typed enums over magic strings.** API method names, parse modes, chat
  actions, and log levels are all backed enums (`ApiMethod`, `ParseMode`,
  `ChatAction`, `LogLevel`), not raw strings, so your IDE can autocomplete
  and typos become compile-time-visible errors.
- **Fail loud by default, but let you opt out.** `BotConfig::$throwExceptions`
  defaults to `true` — API errors throw exceptions rather than returning
  `false` silently. You can flip this off if you prefer manual error
  checking.

## Requirements

- PHP 8.1 or newer (the codebase uses constructor property promotion,
  readonly properties, enums, and first-class callable syntax throughout)
- One of: the `curl` extension (preferred) or the `openssl` extension
  (fallback, used automatically if `curl` isn't available)
- `json` and `mbstring` extensions (standard in virtually every PHP install)

## Where to go next

If you just want to get a bot running as fast as possible, skip ahead to
[Quickstart](04-quickstart.md) — it assumes you've already done the
5-minute [Installation](02-installation.md) step.

If you want the fuller picture first, keep reading in order.

---

[← Documentation Home](README.md) | [Next: Installation →](02-installation.md)
