# API Reference

[← Back to CLAUDE.md](../../CLAUDE.md)

Every service is reached through `TelegramBot` (`src/Bot/TelegramBot.php`), the `final class` facade that wires everything together. Don't instantiate services directly.

## TelegramBot (`src/Bot/TelegramBot.php`)

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

## BotFactory (`src/Bot/BotFactory.php`)

Static factory for common construction patterns.

- `BotFactory::create(?string $token): TelegramBot`
- `BotFactory::createWithConfig(BotConfig $config): TelegramBot`
- `BotFactory::createWithHttpClient(?string $token, HttpClientInterface $client): TelegramBot`

## MessageService (`src/Api/Methods/MessageService.php`)

**Core Feature: Auto-escaping for MarkdownV2.** When `parse_mode => 'MarkdownV2'` is set, the `text` and `caption` fields are automatically escaped. Use `*Raw()` methods to bypass this (when text is already formatted with MarkdownV2 syntax).

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

## MediaService (`src/Api/Methods/MediaService.php`)

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

Only the first item's `caption` / `parse_mode` is shown in the album notification. Telegram accepts 2–10 items per group.

**Local file uploads in a media group:** pass a `CURLFile` as an item's `media` (or `thumbnail`) value. `MediaService::sendMediaGroup()` extracts each `CURLFile` into a top-level `attach://` field (`media_attach_0`, `media_attach_1`, ...) and JSON-encodes the `media` array before the request reaches the HTTP client — this is required by Telegram, which expects `media` as a JSON string plus separate multipart fields for each attached file, not an embedded file object. Both `CurlHttpClient` and `StreamHttpClient` then upload those `attach://`-named fields as real multipart file parts via the shared `MultipartRequestTrait`.

## ChatService (`src/Api/Methods/ChatService.php`)

**Methods:** `sendAction()`, `getChat()`, `getMember()`, `getAdministrators()`, `getMemberCount()`, `banMember()`, `unbanMember()`, `restrictMember()`, `promoteMember()`, `leave()`, `pinMessage()`, `unpinMessage()`, `unpinAllMessages()`, `setChatTitle()`, `setChatDescription()`, `setChatPhoto()`, `deleteChatPhoto()`, `setChatPermissions()`, `getMenuButton()`, `setMenuButton()`, `answerCallbackQuery()`

## PollsService (`src/Api/Methods/PollsService.php`)

**Methods:** `send(array)`, `stop(array)`, `close(array)`

## InlineService (`src/Api/Methods/InlineService.php`)

**Methods:** `answer(array)` — answer inline queries; builder methods for result types: `createArticle()`, `createPhoto()`, `createVideo()`, `createAudio()`, `createDocument()`, `createLocation()`, `createVenue()`, `createContact()`, `createGame()`

## InviteLinksService (`src/Api/Methods/InviteLinksService.php`)

**Methods:** `create()`, `edit()`, `revoke()`, `export()`, `get()`, `getCounts()`, `getMembers()`, `editSubscription()`

## TopicsService (`src/Api/Methods/TopicsService.php`)

**Methods:** `create()`, `edit()`, `close()`, `reopen()`, `delete()`, `unpinAll()`, `editGeneral()`, `closeGeneral()`, `reopenGeneral()`, `hideGeneral()`, `unhideGeneral()`, `get()`, `getAll()`, `getIconStickers()`

## WebhookService (`src/Api/Methods/WebhookService.php`)

**Methods:** `set(array)`, `getInfo()`, `delete(array)`

## GamesService (`src/Api/Methods/GamesService.php`)

**Methods:** `sendGame(array $params): array`, `setGameScore(array $params): mixed` (Message or `true`), `getGameHighScores(array $params): array`

`setGameScore()` / `getGameHighScores()` require exactly one of `chat_id` + `message_id`, or `inline_message_id`. `InlineService::createGame()` only builds the inline-query result payload (`type: game`) for search results — it does not send a game message.

## PaymentsService (`src/Api/Methods/PaymentsService.php`)

**Methods:** `sendInvoice(array $params): array`

`provider_token` may be omitted (or an empty string) for Telegram Stars payments, using currency `XTR`.

## CommandHandler (`src/Command/CommandHandler.php`)

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

See [webhooks-and-bulk.md](webhooks-and-bulk.md) for a full command-registration + webhook example.

## ApiService (`src/Api/ApiService.php`)

Core orchestrator for all Telegram API calls. `final class`.

- `call(ApiMethod $method, array $params = []): mixed`
- `getBulkManager(): BulkOperationManager`
- `getConfig(): BotConfig`

Sanitises params before logging (removes `token`).

## Bulk Operations (`src/Bulk/`)

Parallel execution using `curl_multi_exec`. Managed by `BulkOperationManager`, called via service methods. Usage examples in [webhooks-and-bulk.md](webhooks-and-bulk.md).

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

## Configuration (`src/Config/`)

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

**`src/dotenv.php`** — Thin wrapper that calls `EnvLoader` and auto-loads `.env` on `require`. Not included automatically by `autoload.php`; use `EnvLoader` directly or `require 'src/dotenv.php'` for the auto-load shortcut.

## Logging System (`src/Logging/`)

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
