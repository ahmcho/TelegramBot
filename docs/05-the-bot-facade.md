[← Documentation Home](README.md)

# The Bot Facade

`TelegramBot` (`src/Bot/TelegramBot.php`) is the single entry point into
this framework. It's a `final class` — you're not meant to extend it —
that wires together every service and exposes them through simple accessor
methods. This page is the map of what's inside it and how the pieces fit
together, so the rest of the documentation makes sense in context.

## Why a facade?

Telegram's Bot API has 70+ methods spanning very different domains:
sending messages, managing chat permissions, running polls, forum topics,
payments. Cramming all of that onto one object would make a 2,000-line
class with no discoverability. Instead, `TelegramBot` groups them into
**services**, one per domain, and exposes each with a short accessor:

```php
$bot->messages()      // MessageService  — send/edit/delete/forward text messages
$bot->media()         // MediaService    — photos, documents, video, albums, file downloads
$bot->chats()         // ChatService     — chat info, admin actions, permissions
$bot->webhooks()      // WebhookService  — set/get/delete the webhook
$bot->polls()         // PollsService    — send/stop/close polls
$bot->inline()        // InlineService   — answer inline queries, build result types
$bot->topics()        // TopicsService   — forum topic management
$bot->inviteLinks()   // InviteLinksService — create/edit/revoke invite links
$bot->games()         // GamesService    — send games, manage scores
$bot->payments()      // PaymentsService — send invoices
$bot->commands()      // CommandHandler  — command routing (see docs/10-commands.md)
$bot->api()           // ApiService      — the low-level call() escape hatch
$bot->formatter()     // TextFormatterInterface — MarkdownV2Formatter by default
$bot->getLogger()     // ?LoggerInterface — null if logging is disabled
```

Each of these has its own documentation page — this page is about how they
relate, not what every method does.

## What happens when you construct a `TelegramBot`

```php
$bot = new TelegramBot($token, $config, $httpClient);
```

All three arguments are optional. Walking through the constructor
(`src/Bot/TelegramBot.php:52-95`):

1. **Loads `.env`** via `EnvLoader` — always, regardless of whether you
   passed a token or config explicitly.
2. **Resolves configuration**: if you didn't pass a `BotConfig`, it builds
   one from the token you passed, or from `TELEGRAM_BOT_TOKEN` in the
   environment if you passed neither (throws if that's also missing — see
   [Configuration](03-configuration.md)).
3. **Creates a logger** from the config via `LoggerFactory` (`null` if
   logging is disabled — see [Logging](19-logging.md)).
4. **Creates an HTTP client**: if you didn't pass one, `HttpClientFactory`
   picks `CurlHttpClient` if the `curl` extension is loaded, otherwise
   falls back to `StreamHttpClient` (see [HTTP Clients](20-http-clients.md)).
5. **Creates the bulk manager and `ApiService`**, the low-level layer every
   service ultimately calls through.
6. **Instantiates every service**, each wired to the same `ApiService`.

The upshot: one `TelegramBot` instance holds one shared HTTP client, one
shared logger, and one shared config across every service you call from
it. You typically construct exactly one per request (webhook mode) or once
at the top of your script (long-polling mode).

## The architecture, top to bottom

```
Your code
    ↓
TelegramBot (facade)          — service accessors, webhook helpers, retry wrappers
    ↓
Service classes (MessageService, MediaService, ChatService, ...)
    ↓  each service calls:
ApiService                    — the one place that turns an ApiMethod + params into an HTTP call
    ↓
HttpClientInterface (CurlHttpClient or StreamHttpClient)
    ↓
Telegram Bot API (api.telegram.org)
```

Every service class follows the same shape:

```php
class ChatService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {}

    public function getChat(array $params): array
    {
        return $this->apiService->call(ApiMethod::GET_CHAT, $params);
    }
}
```

It's a thin, typed wrapper: your params array goes in, `ApiService::call()`
handles serialization, HTTP, response parsing, and exception translation,
and you get back Telegram's decoded JSON response (or a scalar, for
methods like `deleteMessage` that return a bare `true`).

## `ApiMethod`: the method registry

`src/Enums/ApiMethod.php` is a backed enum listing every Telegram API
method this framework supports, e.g. `ApiMethod::SEND_MESSAGE =
'sendMessage'`. Every service call goes through one of these — you'll
never see a raw string like `'sendMessage'` passed around internally. If
you ever need to call a method that has an `ApiMethod` case but no
dedicated service wrapper yet, you can drop to the low-level escape hatch:

```php
$response = $bot->api()->call(ApiMethod::GET_ME);
```

`$bot->api()` returns the same `ApiService` every service class uses
internally — there is no meaningful difference in behavior between calling
through a service method and calling `api()->call()` directly, other than
services also apply domain logic (like MarkdownV2 auto-escaping) before
the call.

## Convenience methods (backward compatibility)

A handful of top-level shortcuts exist directly on `TelegramBot` for the
most common operations, mostly kept for ergonomics and backward
compatibility with earlier, less service-oriented versions of the API:

```php
$bot->sendMessage($params);   // delegates to $bot->messages()->send($params)
$bot->sendPhoto($params);     // delegates to $bot->media()->sendPhoto($params)
$bot->getMe(): array;
$bot->getUpdates(array $params = []): array;
```

New code should generally prefer the explicit service form
(`$bot->messages()->send(...)`) since it makes clear which service you're
using and unlocks methods the shortcuts don't cover (like
`sendRaw()`, `editText()`, `sendBulk()` — see
[Sending Messages](06-sending-messages.md)).

## Webhook helpers

```php
$bot->getWebhookUpdates(): ?array   // parses php://input, returns null if empty/invalid
$bot->processWebhook(callable $handler): void  // calls $handler($update) if an update was received
$bot->setInputSource(string $source): void     // override php://input, used in tests
```

Full detail in [Webhooks](11-webhooks.md).

## Retry helpers

```php
$bot->sendMessageWithRetry(array $params, array $options = []): array
$bot->sendBulkWithRetry(array $messages, array $bulkOptions = [], array $retryOptions = []): mixed
$bot->executeWithRetry(callable $callback, array $options = []): mixed
```

These wrap any operation with exponential-backoff retry and Telegram
rate-limit awareness. Full detail in
[Retry & Resilience](17-retry-and-resilience.md).

## `BotFactory`: alternate construction patterns

If you don't want to call `new TelegramBot(...)` directly, `BotFactory`
(`src/Bot/BotFactory.php`) offers named constructors:

```php
use AhmCho\Telegram\Bot\BotFactory;

BotFactory::create(?string $token = null): TelegramBot;
BotFactory::createWithConfig(BotConfig $config): TelegramBot;
BotFactory::createWithHttpClient(?string $token, HttpClientInterface $client): TelegramBot;
```

These are pure convenience — each ultimately constructs a `TelegramBot`
the normal way. Use whichever reads more clearly at your call site.

---

[← Previous: Quickstart](04-quickstart.md) | [Documentation Home](README.md) | [Next: Sending Messages →](06-sending-messages.md)
