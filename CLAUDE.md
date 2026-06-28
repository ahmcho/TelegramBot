# CLAUDE.md - Developer & AI Assistant Guide

## What is This Framework?

Modern, dependency-free PHP 8.1+ Telegram Bot Framework with a clean, service-oriented interface. Zero external dependencies required.

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
  - MediaService
  - ChatService
  - WebhookService
  - PollsService
  - InlineService
  - InviteLinksService
  - TopicsService
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
│   │   └── Methods/
│   │       ├── MessageService.php
│   │       ├── MediaService.php
│   │       ├── ChatService.php
│   │       ├── WebhookService.php
│   │       ├── PollsService.php
│   │       ├── InlineService.php
│   │       ├── InviteLinksService.php
│   │       └── TopicsService.php
│   ├── Bot/
│   │   ├── TelegramBot.php
│   │   └── BotFactory.php
│   ├── Bulk/
│   ├── Client/
│   ├── Command/
│   ├── Config/
│   ├── Enums/
│   ├── Exception/
│   ├── Formatting/
│   ├── Keyboard/
│   └── Logging/
├── public/
│   └── webhook.php
├── examples/
└── tests/
```

---

## Key Components

### TelegramBot (`src/Bot/TelegramBot.php`)

Main facade providing unified access to all framework functionality.

**Service Accessors:**

- `messages()` → MessageService
- `media()` → MediaService
- `chats()` → ChatService
- `webhooks()` → WebhookService
- `polls()` → PollsService
- `inline()` → InlineService
- `topics()` → TopicsService
- `inviteLinks()` → InviteLinksService
- `commands()` → CommandHandler
- `api()` → ApiService
- `formatter()` → TextFormatterInterface (default: MarkdownV2Formatter)

**Webhook Methods:**

- `getWebhookUpdates()` - Parse webhook input
- `processWebhook(callable)` - Process updates via handler
- `setInputSource(string)` - Override input (testing)

**Retry Methods:**

- `sendMessageWithRetry(array $params, array $options)` - Exponential backoff, rate-limit aware
- `sendBulkWithRetry(array $messages, array $bulkOptions, array $retryOptions)`
- `executeWithRetry(callable $callback, array $options)` - Generic retry wrapper

Retry options: `max_retries` (default 3), `initial_delay_ms` (default 1000), `max_delay_ms` (default 10000), `on_retry` (callable).

### MessageService (`src/Api/Methods/MessageService.php`)

**Core Feature: Auto-escaping for MarkdownV2**

When `parse_mode => 'MarkdownV2'` is set, `text` and `caption` are automatically escaped.

**Methods:**

- `send()` / `sendRaw()` — with / without auto-escape
- `editText()` / `editTextRaw()` — edit message text
- `editCaption()` / `editCaptionRaw()` — edit caption
- `delete()`, `forward()`, `copy()`
- `sendBulk(array $messages, array $options)` / `sendBulkRaw()` — batch send, returns `BulkResult`
- `broadcast(array $chatIds, string $text, array $params, array $options)` / `broadcastRaw()` — returns `BulkResult`

Use `*Raw()` methods when text is already formatted with MarkdownV2 syntax.

### MediaService (`src/Api/Methods/MediaService.php`)

**Methods:** `sendPhoto()`, `sendDocument()`, `sendVideo()`, `sendAudio()`, `sendVoice()`, `sendAnimation()`, `sendSticker()`, `sendLocation()`, `sendVenue()`, `sendContact()`, `sendPoll()`, `sendDice()`, `getCustomEmojiStickers()`

**Input types:** File ID (string), URL (string), CURLFile (local upload)

### ChatService (`src/Api/Methods/ChatService.php`)

**Methods:** `sendAction()`, `getChat()`, `getMember()`, `getAdministrators()`, `getMemberCount()`, `banMember()`, `unbanMember()`, `restrictMember()`, `promoteMember()`, `leave()`, `pinMessage()`, `unpinMessage()`, `unpinAllMessages()`, `getMenuButton()`, `setMenuButton()`, `answerCallbackQuery()`

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

### CommandHandler (`src/Command/CommandHandler.php`)

Built-in command routing system, accessible via `$bot->commands()`.

**Methods:**

- `register(string $command, callable $callback, string $description): self`
- `registerCommands(array $commands): self`
- `setDefault(callable): self` — handles unknown commands
- `addMiddleware(string $name, callable): self` — runs before command; return false to halt
- `handleUpdate(array $update): bool`
- `generateHelp(): string` — builds help text from registered descriptions
- `sendHelp(int $chatId): void`
- `hasCommand(string): bool`, `unregister(string): bool`, `clear(): void`

Callback signature: `function(TelegramBot $bot, int $chatId, array $args): void`

### ApiService (`src/Api/ApiService.php`)

Core orchestrator for all Telegram API calls.

- `call(ApiMethod $method, array $params): mixed`
- `getBulkManager(): BulkOperationManager`
- `getConfig(): BotConfig`

### Bulk Operations (`src/Bulk/`)

Parallel execution using `curl_multi_exec`. Managed by `BulkOperationManager`, called via service methods.

**`BulkResult`** is a `readonly class` (not an array):

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
```

Throws `BulkSendException` (carries the `BulkResult`) if all requests fail and `throwExceptions` is enabled.

**Configuration options** (passed as second arg to `sendBulk`/`broadcast`):

- `max_concurrent` — default 30
- `delay_ms` — default 0

### Configuration (`src/Config/`)

**BotConfig** — Immutable with builder-style mutators:

```php
$config = new BotConfig(
    token: '123:ABC',
    apiUrl: 'https://api.telegram.org/', // Optional
    timeout: 30,                          // Optional, seconds
    throwExceptions: true,                // Optional
    verifySsl: false,                     // Optional (disable for local dev)
    loggingEnabled: true,                 // Optional
    logFilePath: 'logs/bot.log',          // Optional
    logLevel: 'INFO'                      // Optional
);

// Fluent mutators return new instances:
$config->withTimeout(60)
       ->withLoggingEnabled(false)
       ->withLogFilePath('logs/bot.log')
       ->withLogLevel('DEBUG');
```

**EnvLoader** — Loads `.env`, searches multiple paths, supports quoted/unquoted values, skips comments.

### Logging System (`src/Logging/`)

PSR-3 compliant, file-based with locking. Auto-created from `BotConfig` by `LoggerFactory`.

- **`LoggerFactory::createFromConfig(BotConfig): ?LoggerInterface`** — returns null when logging disabled
- **`LoggerFactory::create(array): LoggerInterface`** — from config array
- **`LoggerFactory::createNull(): LoggerInterface`** — no-op (useful in tests)
- **`Logger`** — writes to file via `FileLogHandler` with retry and `LOCK_EX`
- **`NullLogger`** — all methods are no-ops; used when logging is off
- **`LogLevel` enum** — DEBUG, INFO, WARNING, ERROR, CRITICAL (with PSR-3 conversion)

All framework code checks `if ($this->logger !== null)` — logging never throws.

---

## How to Work With This Codebase

### Where Logic Lives

**Service Layer** → Domain-specific business logic

- MessageService: Text formatting, auto-escaping
- MediaService: File handling
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
- Namespace matches directory structure
- `declare(strict_types=1);` at top of every file
- Public methods must have type annotations; complex arrays use `@param array<key, type>`

---

## Exception Handling

```
TelegramException (abstract)
├── ApiException          - Telegram API errors (4xx, 5xx)
├── HttpClientException   - HTTP layer errors (network, DNS, timeout)
└── BulkSendException     - Bulk operation total failure (carries BulkResult)
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

**MarkdownV2Formatter** — auto-escape applied automatically by `MessageService` when `parse_mode = 'MarkdownV2'`. For manual formatting:

```php
$formatter = $bot->formatter(); // MarkdownV2Formatter
$bold = $formatter->bold('Bold');
$italic = $formatter->italic('Italic');
```

**HtmlFormatter** — does not auto-escape; use for manual HTML formatting to avoid conflicts:

```php
$formatter = new HtmlFormatter();
$bold = $formatter->bold('Bold');
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

**Button types:** `Button::callback()`, `Button::url()`, `Button::switchInline()`, `Button::switchInlineCurrent()`, `Button::text()`

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

Note: `ReplyKeyboardBuilder::addRow()` accepts one or more `Button` objects per row; only the button text is used (actions are ignored for reply keyboards).

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
├── Helpers/        # MockHttpClient, MockTelegramResponse, TestDataFactory
└── bootstrap.php
```

`MockHttpClient` implements `HttpClientInterface` and records all requests for assertion.

---

## Critical Files Reference

| File                                      | Purpose                       |
| ----------------------------------------- | ----------------------------- |
| `autoload.php`                            | PSR-4 autoloader              |
| `src/Bot/TelegramBot.php`                 | Main facade                   |
| `src/Config/BotConfig.php`                | Immutable configuration       |
| `src/Config/EnvLoader.php`                | .env loader                   |
| `src/Api/Methods/MessageService.php`      | Message ops + auto-escape     |
| `src/Bulk/BulkOperationManager.php`       | Parallel requests             |
| `src/Bulk/BulkResult.php`                 | Typed bulk result value object |
| `src/Command/CommandHandler.php`          | Command routing               |
| `src/Logging/LoggerFactory.php`           | Logger creation               |
| `public/webhook.php`                      | Production webhook endpoint   |

---

**Last Updated:** 2026-06-28
**Framework Version:** 1.1
**PHP Version:** 8.1+
