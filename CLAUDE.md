# CLAUDE.md - Developer & AI Assistant Guide

## What is This Framework?

Modern, dependency-free PHP 8.1+ Telegram Bot Framework with a clean, service-oriented interface. Zero external dependencies required.

Namespace root: `AhmCho\Telegram`

---

## Architecture Overview

```
Application Layer (User Code)
         ↓
Facade Layer (TelegramBot)
  - Service Accessors
  - Webhook Handling
  - Retry Logic
         ↓
Service Layer
  - MessageService (auto-escaping)
  - MediaService (auto-escaping captions)
  - ChatService
  - WebhookService
  - PollsService
  - InlineService
  - InviteLinksService
  - TopicsService
  - GamesService
  - PaymentsService
         ↓
API Layer (ApiService)
  - Method Routing
  - Bulk Operations
         ↓
Client Layer (HttpClientInterface)
  - CurlHttpClient (default)
  - StreamHttpClient (fallback)
  - HttpClientFactory (auto-selects)
         ↓
Infrastructure
  - BotConfig (immutable)
  - EnvLoader (.env)
  - Enums (ApiMethod, HttpMethod, ParseMode, ChatAction, LogLevel)
  - Exception Hierarchy
  - Logging System (PSR-3)
```

---

## Design Patterns

| Pattern       | Location                             | Purpose                      |
| ------------- | ------------------------------------ | ---------------------------- |
| Facade        | `src/Bot/TelegramBot.php`            | Unified interface            |
| Factory       | `src/Bot/BotFactory.php`             | Pre-configured instances     |
| Builder       | `src/Keyboard/*Builder.php`          | Fluent keyboard construction |
| Service Layer | `src/Api/Methods/`                   | Domain-specific operations   |
| Strategy      | `src/Client/HttpClientInterface.php` | Swappable HTTP clients       |

---

## Directory Structure

```
tg-bots/
├── autoload.php
├── CLAUDE.md
├── README.md
├── .env.example
├── src/
│   ├── Api/
│   │   ├── ApiService.php
│   │   └── Methods/
│   │       ├── MessageService.php
│   │       ├── MediaService.php
│   │       ├── ChatService.php
│   │       ├── WebhookService.php
│   │       ├── PollsService.php
│   │       ├── InlineService.php
│   │       ├── InviteLinksService.php
│   │       ├── TopicsService.php
│   │       ├── GamesService.php
│   │       └── PaymentsService.php
│   ├── Bot/
│   │   ├── TelegramBot.php
│   │   └── BotFactory.php
│   ├── Bulk/
│   │   ├── BulkOperationManager.php
│   │   ├── BulkResult.php
│   │   └── BulkSendException.php
│   ├── Client/
│   │   ├── CurlHttpClient.php
│   │   ├── StreamHttpClient.php
│   │   ├── HttpClientFactory.php
│   │   ├── HttpClientInterface.php
│   │   └── Traits/ResponseParserTrait.php
│   ├── Command/
│   │   └── CommandHandler.php
│   ├── Config/
│   │   ├── BotConfig.php
│   │   └── EnvLoader.php
│   ├── Enums/
│   │   ├── ApiMethod.php
│   │   ├── HttpMethod.php
│   │   ├── ParseMode.php
│   │   ├── ChatAction.php
│   │   └── LogLevel.php
│   ├── Exception/
│   │   ├── TelegramException.php
│   │   ├── ApiException.php
│   │   └── HttpClientException.php
│   ├── Formatting/
│   │   ├── TextFormatterInterface.php
│   │   ├── MarkdownV2Formatter.php
│   │   └── HtmlFormatter.php
│   ├── Keyboard/
│   │   ├── Button.php
│   │   ├── InlineKeyboardBuilder.php
│   │   ├── ReplyKeyboardBuilder.php
│   │   ├── ReplyKeyboardOptions.php
│   │   └── KeyboardBuilderInterface.php
│   ├── Logging/
│   │   ├── Logger.php
│   │   ├── NullLogger.php
│   │   ├── LoggerFactory.php
│   │   ├── LoggerInterface.php
│   │   ├── LogLevel.php
│   │   ├── FileLogHandler.php
│   │   ├── Context/ExceptionContext.php
│   │   └── Traits/LoggerHelperTrait.php
│   ├── Traits/
│   │   └── MarkdownV2EscapeTrait.php
│   ├── Psr/Log/
│   │   └── LoggerInterface.php
│   └── dotenv.php
├── public/
│   └── webhook.php
├── examples/
└── tests/
```

---

## Key Components

### TelegramBot (`src/Bot/TelegramBot.php`)

Main facade — `final class`. All services are wired here.

**Service Accessors:**

- `messages()` → MessageService
- `media()` → MediaService
- `chats()` → ChatService
- `webhooks()` → WebhookService
- `polls()` → PollsService
- `inline()` → InlineService
- `topics()` → TopicsService
- `inviteLinks()` → InviteLinksService
- `games()` → GamesService
- `payments()` → PaymentsService
- `commands()` → CommandHandler
- `api()` → ApiService
- `formatter()` → TextFormatterInterface (default: MarkdownV2Formatter)
- `getLogger()` → `?LoggerInterface` (null when logging disabled)

**Convenience Methods (backward compatibility):**

- `sendMessage(array $params): array` — delegates to `messages()->send()`
- `sendPhoto(array $params): array` — delegates to `media()->sendPhoto()`
- `getMe(): array`
- `getUpdates(array $params = []): array`

**Webhook Methods:**

- `getWebhookUpdates(): ?array` — parses `php://input`
- `processWebhook(callable $handler): void` — calls handler if update is non-null
- `setInputSource(string $source): void` — override `php://input` (for testing)

**Retry Methods:**

- `sendMessageWithRetry(array $params, array $options = []): array`
- `sendBulkWithRetry(array $messages, array $bulkOptions = [], array $retryOptions = []): mixed`
- `executeWithRetry(callable $callback, array $options = []): mixed` — generic retry wrapper

Retry `$options` keys: `max_retries` (default 3), `initial_delay_ms` (default 1000), `max_delay_ms` (default 10000), `on_retry` (callable: `fn(int $attempt, Exception $e, int $delayMs)`).

Retry behaviour: no retry on 4xx except 429; honours `retry_after` from Telegram on 429; exponential backoff otherwise.

### BotFactory (`src/Bot/BotFactory.php`)

Static factory for common construction patterns.

- `BotFactory::create(?string $token): TelegramBot`
- `BotFactory::createWithConfig(BotConfig $config): TelegramBot`
- `BotFactory::createWithHttpClient(?string $token, HttpClientInterface $client): TelegramBot`

### MessageService (`src/Api/Methods/MessageService.php`)

**Core Feature: Auto-escaping for MarkdownV2**

When `parse_mode => 'MarkdownV2'` is set, the `text` and `caption` fields are automatically escaped. Use `*Raw()` methods to bypass this (when text is already formatted with MarkdownV2 syntax).

**Methods:**

- `send(array $params): array` — auto-escapes
- `sendRaw(array $params): array`
- `editText(array $params): array` — auto-escapes
- `editTextRaw(array $params): array`
- `editCaption(array $params): array` — auto-escapes
- `editCaptionRaw(array $params): array`
- `delete(array $params): mixed`
- `forward(array $params): array`
- `copy(array $params): array`
- `sendBulk(array $messages, array $options = []): BulkResult` — auto-escapes each message
- `sendBulkRaw(array $messages, array $options = []): BulkResult`
- `broadcast(array $chatIds, string $text, array $commonParams = [], array $options = []): BulkResult` — auto-escapes
- `broadcastRaw(array $chatIds, string $text, array $commonParams = [], array $options = []): BulkResult`

### MediaService (`src/Api/Methods/MediaService.php`)

Auto-escaping applies to captioned methods (`sendPhoto`, `sendDocument`, `sendVideo`, `sendAudio`, `sendVoice`, `sendAnimation`) when `parse_mode => 'MarkdownV2'` is set.

**Methods:** `sendPhoto()`, `sendDocument()`, `sendVideo()`, `sendAudio()`, `sendVoice()`, `sendAnimation()`, `sendSticker()`, `sendLocation()`, `sendVenue()`, `sendContact()`, `sendPoll()`, `sendDice()`, `getCustomEmojiStickers()`, `sendMediaGroup()`, `getFile()`, `getFileDownloadUrl()`

**Input types:** File ID (string), URL (string), `CURLFile` (local upload)

**`sendMediaGroup()` — `media` array structure:**

Each element must be an `InputMedia*` array with at minimum `type` and `media`:

```php
$bot->media()->sendMediaGroup([
    'chat_id' => $chatId,
    'media' => [
        ['type' => 'photo',    'media' => 'file_id_or_url', 'caption' => 'optional'],
        ['type' => 'video',    'media' => 'file_id_or_url', 'width' => 1280, 'height' => 720],
        ['type' => 'audio',    'media' => 'file_id_or_url', 'title' => 'Song', 'performer' => 'Artist'],
        ['type' => 'document', 'media' => 'file_id_or_url'],
    ],
]);
// Returns array of Message objects, one per media item.
```

Only the first item's `caption` / `parse_mode` is shown in the album notification. Telegram accepts 2–10 items per group. Local file uploads use `CURLFile` in the `media` field and an `attach://` reference is handled automatically by the HTTP client.

### ChatService (`src/Api/Methods/ChatService.php`)

**Methods:** `sendAction()`, `getChat()`, `getMember()`, `getAdministrators()`, `getMemberCount()`, `banMember()`, `unbanMember()`, `restrictMember()`, `promoteMember()`, `leave()`, `pinMessage()`, `unpinMessage()`, `unpinAllMessages()`, `setChatTitle()`, `setChatDescription()`, `setChatPhoto()`, `deleteChatPhoto()`, `setChatPermissions()`, `getMenuButton()`, `setMenuButton()`, `answerCallbackQuery()`

### PollsService (`src/Api/Methods/PollsService.php`)

**Methods:** `send(array)`, `stop(array)`, `close(array)`

### InlineService (`src/Api/Methods/InlineService.php`)

**Methods:** `answer(array)` — answer inline queries; builder methods for result types: `createArticle()`, `createPhoto()`, `createVideo()`, `createAudio()`, `createDocument()`, `createLocation()`, `createVenue()`, `createContact()`, `createGame()`

### InviteLinksService (`src/Api/Methods/InviteLinksService.php`)

**Methods:** `create()`, `edit()`, `revoke()`, `export()`, `get()`, `getCounts()`, `getMembers()`, `editSubscription()`

### TopicsService (`src/Api/Methods/TopicsService.php`)

**Methods:** `create()`, `edit()`, `close()`, `reopen()`, `delete()`, `unpinAll()`, `editGeneral()`, `closeGeneral()`, `reopenGeneral()`, `hideGeneral()`, `unhideGeneral()`, `get()`, `getAll()`, `getIconStickers()`

### WebhookService (`src/Api/Methods/WebhookService.php`)

**Methods:** `set(array)`, `getInfo()`, `delete(array)`

### GamesService (`src/Api/Methods/GamesService.php`)

**Methods:** `sendGame(array $params): array`, `setGameScore(array $params): mixed` (Message or `true`), `getGameHighScores(array $params): array`

`setGameScore()` / `getGameHighScores()` require exactly one of `chat_id` + `message_id`, or `inline_message_id`. `InlineService::createGame()` only builds the inline-query result payload (`type: game`) for search results — it does not send a game message.

### PaymentsService (`src/Api/Methods/PaymentsService.php`)

**Methods:** `sendInvoice(array $params): array`

`provider_token` may be omitted (or an empty string) for Telegram Stars payments, using currency `XTR`.

### CommandHandler (`src/Command/CommandHandler.php`)

Built-in command routing system, accessible via `$bot->commands()`.

**Methods:**

- `register(string $command, callable $callback, string $description = ''): self`
- `registerCommands(array $commands): self` — accepts `['cmd' => callable]` or `['cmd' => ['callback' => callable, 'description' => string]]`
- `setDefault(callable $callback): self` — handles unknown commands; signature: `function(TelegramBot $bot, int $chatId, string $command, array $args): void`
- `addMiddleware(string $name, callable $middleware): self` — runs before commands; signature: `function(TelegramBot $bot, int $chatId, string $command, array $args): bool` — return `false` to halt
- `handleUpdate(array $update): bool`
- `generateHelp(): string` — builds help text from registered descriptions
- `sendHelp(int $chatId): void` — sends MarkdownV2-formatted help message
- `getRegisteredCommands(): array` — returns list of command name strings
- `hasCommand(string $command): bool`
- `unregister(string $command): bool`
- `clear(): void` — clears commands, descriptions, and middleware

Command callback signature: `function(TelegramBot $bot, int $chatId, array $args): void`

Commands are normalised (lowercased, leading `/` stripped) on register and lookup.

### ApiService (`src/Api/ApiService.php`)

Core orchestrator for all Telegram API calls. `final class`.

- `call(ApiMethod $method, array $params = []): mixed`
- `getBulkManager(): BulkOperationManager`
- `getConfig(): BotConfig`

Sanitises params before logging (removes `token`).

### Bulk Operations (`src/Bulk/`)

Parallel execution using `curl_multi_exec`. Managed by `BulkOperationManager`, called via service methods.

**`BulkResult`** is a `readonly class` implementing `Countable`:

```php
$result->total;            // int
$result->successful;       // int
$result->failed;           // int
$result->results;          // array of per-request results
$result->errors;           // array of error strings
$result->isSuccess();      // bool — true if failed === 0
$result->hasFailures();    // bool
$result->getSuccessRate(); // float — percentage
$result->getFailedResults();
$result->getSuccessfulResults();
count($result);            // Countable

// Static factories
BulkResult::fromRawResults(array $rawResults): self
BulkResult::empty(): self
```

Throws `BulkSendException` (carries the `BulkResult`) if any requests fail and `throwExceptions` is enabled.

**Configuration options** (second arg to `sendBulk`/`broadcast`):

- `max_concurrent` — default 30
- `delay_ms` — default 0

### Configuration (`src/Config/`)

**BotConfig** — Immutable with builder-style mutators. Actual defaults:

```php
$config = new BotConfig(
    token: '123:ABC',
    apiUrl: 'https://api.telegram.org/', // default
    timeout: 30,                          // default, seconds
    throwExceptions: true,                // default
    verifySsl: true,                      // default — set false only for local dev
    loggingEnabled: true,                 // default
    logFilePath: 'bot.log',              // default
    logLevel: 'INFO'                      // default
);

// Fluent mutators — each returns a new instance:
$config->withVerifySsl(false)        // disable SSL for local dev
       ->withTimeout(60)
       ->withThrowExceptions(false)
       ->withLoggingEnabled(false)
       ->withLogFilePath('logs/bot.log')
       ->withLogLevel('DEBUG');
```

**EnvLoader** (`src/Config/EnvLoader.php`) — Loads `.env`, searches multiple paths, supports quoted/unquoted values, skips comments.

**`src/dotenv.php`** — Thin wrapper that calls `EnvLoader` and auto-loads `.env` on `require`. Included automatically by `autoload.php` is NOT assumed; use `EnvLoader` directly or `require 'src/dotenv.php'` for the auto-load shortcut.

### Logging System (`src/Logging/`)

PSR-3 compliant, file-based with `LOCK_EX`. Auto-created from `BotConfig` by `LoggerFactory`.

- **`LoggerFactory::createFromConfig(BotConfig): ?LoggerInterface`** — returns `null` when logging disabled
- **`LoggerFactory::create(array): LoggerInterface`** — from config array with keys `log_file_path`, `log_level`
- **`LoggerFactory::createDefault(): LoggerInterface`** — uses `bot.log` / `INFO`
- **`LoggerFactory::createNull(): LoggerInterface`** — no-op (useful in tests)
- **`Logger`** — writes to file via `FileLogHandler` with retry and `LOCK_EX`
- **`NullLogger`** — all methods are no-ops; used when logging is off
- **`LogLevel` enum** — DEBUG, INFO, WARNING, ERROR, CRITICAL (with PSR-3 conversion)

All framework code checks `if ($this->logger !== null)` — logging never throws.

**`LoggerHelperTrait`** (`src/Logging/Traits/LoggerHelperTrait.php`) — used internally by `TelegramBot`, `ApiService`, `CurlHttpClient`. Provides `logIfEnabled()` and `logExceptionIfEnabled()`.

---

## How to Work With This Codebase

### Where Logic Lives

**Service Layer** → Domain-specific business logic

- MessageService: Text formatting, auto-escaping
- MediaService: File handling, caption auto-escaping
- ChatService: Chat administration
- WebhookService: Webhook management

**API Layer** → Pure HTTP orchestration, no business logic

**Facade Layer** → Entry point; `TelegramBot` wires all services together

### What to Avoid

- Instantiating services directly (use TelegramBot)
- Bypassing ApiService in new service classes
- Calling `$bot->api()->call()` directly when a service method exists
- Hardcoding tokens or configuration
- Mixing HTTP concerns into service classes

### How to Extend Safely

**Adding New API Methods:**

1. Add enum value to `ApiMethod` (`src/Enums/ApiMethod.php`)
2. Add method to appropriate service class using `$this->apiService->call(ApiMethod::YOUR_METHOD, $params)`
3. Add accessor to `TelegramBot` if service is new

**Adding New Services:**

```php
<?php
declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;

class YourDomainService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {}

    public function doSomething(array $params): array
    {
        return $this->apiService->call(ApiMethod::YOUR_METHOD, $params);
    }
}
```

Add the service to `TelegramBot`'s constructor and add an accessor method.

**Using Custom HTTP Client:**

```php
$httpClient = new CustomHttpClient();
$config = new BotConfig(token: 'your_token');
$bot = new TelegramBot(null, $config, $httpClient);
```

---

## Code Conventions

| Type       | Convention           |
| ---------- | -------------------- |
| Classes    | PascalCase           |
| Methods    | camelCase            |
| Properties | camelCase            |
| Constants  | SCREAMING_SNAKE_CASE |

- One class per file, filename matches class name
- Namespace matches directory structure under `AhmCho\Telegram`
- `declare(strict_types=1);` at top of every file
- Public methods must have type annotations; complex arrays use `@param array<key, type>`

---

## Exception Handling

```
TelegramException (abstract)
├── ApiException          - Telegram API errors (4xx, 5xx)
├── HttpClientException   - HTTP layer errors (network, DNS, timeout)
└── BulkSendException     - Bulk operation failure (carries BulkResult)
```

```php
try {
    $result = $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Hello']);
} catch (ApiException $e) {
    // $e->getHttpCode(), $e->getErrorCode(), $e->getResponseBody()
} catch (HttpClientException $e) {
    // network/DNS/timeout errors
} catch (BulkSendException $e) {
    $result = $e->getResult(); // BulkResult
}
```

---

## Formatters

Both formatters implement `TextFormatterInterface`:

```php
interface TextFormatterInterface
{
    public function escape(string $text): string;
    public function bold(string $text): string;
    public function italic(string $text): string;
    public function underline(string $text): string;
    public function strikethrough(string $text): string;
    public function code(string $text): string;
    public function pre(string $text): string;
    public function link(string $text, string $url): string;
    public function mention(string $text, string $username): string;
    public function hashtag(string $tag): string;
}
```

**MarkdownV2Formatter** — escapes all MarkdownV2 special chars. Auto-escape is applied by `MessageService` and `MediaService` when `parse_mode = 'MarkdownV2'`. For manual formatting:

```php
$f = $bot->formatter(); // MarkdownV2Formatter
$f->bold('text');         // *text*
$f->italic('text');       // _text_
$f->underline('text');    // __text__
$f->strikethrough('text');// ~text~
$f->code('text');         // `text`
$f->pre('text');          // ```\ntext\n```
$f->link('text', $url);
$f->mention('name', $userId);
$f->hashtag('tag');
```

**HtmlFormatter** — wraps in HTML tags, escapes via `htmlspecialchars`. Does not perform auto-escaping in service methods:

```php
$f = new HtmlFormatter();
$f->bold('text');      // <b>text</b>
$f->italic('text');    // <i>text</i>
$f->underline('text'); // <u>text</u>
// ... same interface, HTML output
```

---

## Keyboard Builders

### InlineKeyboardBuilder

```php
$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::callback('Button 1', 'data_1'),
        Button::url('Google', 'https://google.com')
    )
    ->addRow(
        Button::callback('Button 2', 'data_2')
    )
    ->build(); // returns JSON string

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Pick one',
    'reply_markup' => $keyboard,
]);
```

**Button types:** `Button::callback(text, data)`, `Button::url(text, url)`, `Button::switchInline(text, query)`, `Button::switchInlineCurrent(text, query)`, `Button::text(text)`

### ReplyKeyboardBuilder

```php
$options = new ReplyKeyboardOptions(
    resizeKeyboard: true,
    oneTimeKeyboard: true,
    selective: false,
    isPersistent: false
);

$keyboard = ReplyKeyboardBuilder::create($options)
    ->addRow(Button::text('Option 1'), Button::text('Option 2'))
    ->addRow(Button::text('Option 3'))
    ->build();
```

`ReplyKeyboardBuilder::addRow()` accepts one or more `Button` objects; only `text` is used (callback data is ignored for reply keyboards).

---

## Bulk Operations

### sendBulk

```php
$messages = [
    ['chat_id' => 123, 'text' => 'Hello 1'],
    ['chat_id' => 456, 'text' => 'Hello 2'],
];

$result = $bot->messages()->sendBulk($messages);
echo $result->getSuccessRate() . '%';
```

### broadcast

```php
$chatIds = [123, 456, 789];
$result = $bot->messages()->broadcast($chatIds, 'Announcement!');
```

---

## Webhooks

### Setup

```php
$bot = new TelegramBot();

$bot->webhooks()->set([
    'url' => 'https://your-domain.com/public/webhook.php',
    'secret_token' => 'your_secret'
]);
```

### Handling Updates

```php
$update = $bot->getWebhookUpdates();

if ($update && isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    $bot->messages()->send(['chat_id' => $chatId, 'text' => "You said: $text"]);
}
```

### CommandHandler

```php
$bot->commands()
    ->register('start', function(TelegramBot $bot, int $chatId, array $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome!']);
    }, 'Start the bot')
    ->register('help', function(TelegramBot $bot, int $chatId, array $args) {
        $bot->commands()->sendHelp($chatId);
    }, 'Show help');

$bot->processWebhook(function(array $update) use ($bot) {
    $bot->commands()->handleUpdate($update);
});
```

---

## Testing

```bash
vendor/bin/phpunit                    # all tests
vendor/bin/phpunit tests/Unit/Api/    # specific suite
```

```
tests/
├── Unit/           # Unit tests (mocked HTTP)
├── Integration/    # Integration tests
├── Benchmark/      # Bulk operation benchmarks
├── EndToEnd/       # E2E tests
├── Helpers/        # MockHttpClient, MockTelegramResponse, TestDataFactory, WebhookStreamWrapper
└── bootstrap.php
```

`MockHttpClient` implements `HttpClientInterface` and records all requests for assertion.

---

## Critical Files Reference

| File                                      | Purpose                              |
| ----------------------------------------- | ------------------------------------ |
| `autoload.php`                            | PSR-4 autoloader                     |
| `src/Bot/TelegramBot.php`                 | Main facade (final)                  |
| `src/Bot/BotFactory.php`                  | Static construction helpers          |
| `src/Config/BotConfig.php`                | Immutable configuration              |
| `src/Config/EnvLoader.php`                | .env loader                          |
| `src/dotenv.php`                          | Auto-load .env shortcut              |
| `src/Api/Methods/MessageService.php`      | Message ops + auto-escape            |
| `src/Api/Methods/MediaService.php`        | Media ops + caption auto-escape      |
| `src/Bulk/BulkOperationManager.php`       | Parallel requests via curl_multi     |
| `src/Bulk/BulkResult.php`                 | Typed bulk result value object       |
| `src/Command/CommandHandler.php`          | Command routing                      |
| `src/Logging/LoggerFactory.php`           | Logger creation                      |
| `src/Traits/MarkdownV2EscapeTrait.php`    | Auto-escape logic used by services   |
| `src/Logging/Traits/LoggerHelperTrait.php`| Null-safe logging helpers            |
| `public/webhook.php`                      | Production webhook endpoint          |

---

**Last Updated:** 2026-07-02
**Framework Version:** 1.1
**PHP Version:** 8.1+
