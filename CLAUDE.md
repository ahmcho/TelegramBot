# CLAUDE.md - Developer & AI Assistant Guide

## Purpose

Comprehensive context for AI assistants and developers working on **AhmCho\Telegram** framework.

---

## 📌 Important Notice

**Database support removed in version 1.1**

Framework now focuses solely on Telegram Bot API logic. User storage is your responsibility.

---

## What is This Framework?

Modern, dependency-free PHP 8.1+ Telegram Bot Framework with clean, service-oriented interface. Zero external dependencies required.

---

## Architecture Overview

```
Application Layer (User Code)
         ↓
Facade Layer (TelegramBot)
  - Service Accessors
  - Webhook Handling
         ↓
Service Layer
  - MessageService (auto-escaping)
  - MediaService
  - ChatService
  - WebhookService
         ↓
API Layer (ApiService)
  - Method Routing
  - Bulk Operations
         ↓
Client Layer (HttpClientInterface)
  - CurlHttpClient (default)
  - StreamHttpClient (fallback)
         ↓
Infrastructure
  - BotConfig (immutable)
  - EnvLoader (.env)
  - Enums (ApiMethod, HttpMethod, etc.)
  - Exception Hierarchy
  - Logger (optional)
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
│   │       └── WebhookService.php
│   ├── Bot/
│   │   ├── TelegramBot.php
│   │   └── BotFactory.php
│   ├── Bulk/
│   ├── Client/
│   ├── Config/
│   ├── Enums/
│   ├── Exception/
│   ├── Formatting/
│   └── Keyboard/
├── public/
│   └── webhook.php
├── examples/
└── tests/
```

---

## Key Components

### TelegramBot (`src/Bot/TelegramBot.php`)

**Main facade** providing unified access to all framework functionality.

**Service Accessors:**

- `messages()` → MessageService
- `media()` → MediaService
- `chats()` → ChatService
- `webhooks()` → WebhookService
- `api()` → ApiService
- `formatter()` → MarkdownV2Formatter

**Webhook Methods:**

- `getWebhookUpdates()` - Parse webhook input
- `processWebhook()` - Process updates via handler
- `setInputSource()` - Override input (testing)

**Implementation Notes:**

- Constructor auto-loads environment via `EnvLoader`
- All services cached in readonly properties
- Logger instantiated from config

### MessageService (`src/Api/Methods/MessageService.php`)

**Core Feature: Auto-escaping for MarkdownV2**

When `parse_mode => 'MarkdownV2'` is set, `text` and `caption` are automatically escaped to prevent API errors.

**Special Characters Escaped:** `\ _ * [ ] ( ) ~ ` > # + - = | { } . !` (19 characters total)

**Important:** The backslash `\` is escaped first to avoid double-escaping issues.

**Escaping Implementation Details:**

- Backslash is escaped first (before other special characters)
- This prevents scenarios like `\*` from becoming `\\\*` (triple backslash)
- Correct escaping: `\*` → `\\*` (double backslash)
- All escaping uses `str_replace()` for UTF-8 compatibility
- Inside `pre` and `code` entities, only `` ` `` and `\` need to be escaped

**Methods:**

- `send()` - Send with auto-escape
- `sendRaw()` - Send without auto-escape (preserves pre-formatted MarkdownV2)
- `editText()` - Edit text with auto-escape
- `editTextRaw()` - Edit text without auto-escape (preserves pre-formatted MarkdownV2)
- `editCaption()` - Edit caption with auto-escape
- `editCaptionRaw()` - Edit caption without auto-escape (preserves pre-formatted MarkdownV2)
- `delete()`, `forward()`, `copy()`
- `sendBulk()` - Batch with auto-escape
- `sendBulkRaw()` - Batch without auto-escape (preserves pre-formatted MarkdownV2)
- `broadcast()` - Broadcast with auto-escape
- `broadcastRaw()` - Broadcast without auto-escape (preserves pre-formatted MarkdownV2)

**When to use Raw methods:**

Use `*Raw()` methods when you have text that is already formatted with MarkdownV2 and you want to preserve the formatting. This is useful when:
- Echoing back user messages that contain formatting (e.g., `*bold*`, `_italic_`)
- Manually formatting text with MarkdownV2 syntax
- Forwarding pre-formatted content

### MediaService (`src/Api/Methods/MediaService.php`)

**File Types:** Photo, Audio, Document, Video, Animation, Voice, VideoNote, Sticker

**Input Types:**

- File ID (string) - Previous upload
- URL (string) - Telegram downloads
- CURLFile (object) - Local upload

### ApiService (`src/Api/ApiService.php`)

**Core orchestrator** for all Telegram API calls.

**Responsibilities:**

- Constructs full API URLs from bot token
- Delegates to HTTP client
- Provides access to BulkOperationManager
- Manages BotConfig

### Bulk Operations (`src/Bulk/`)

**Parallel execution** using `curl_multi_exec` for optimal performance.

**BulkResult Structure:**

```php
[
    'success' => bool,
    'total' => int,
    'successful' => int,
    'failed' => int,
    'results' => [
        [
            'success' => bool,
            'chat_id' => int|string,
            'message_id' => int|null,
            'data' => array|null,
            'error' => string|null
        ],
        ...
    ]
]
```

**Configuration:**

- `max_concurrent` - Default: 30
- `delay_ms` - Default: 0

### Configuration (`src/Config/`)

**BotConfig** - Immutable configuration with readonly properties:

```php
$config = new BotConfig(
    token: '123:ABC',
    apiUrl: 'https://api.telegram.org/bot', // Optional
    throwExceptions: true, // Optional
    loggingEnabled: true, // Optional
    logLevel: 'INFO', // Optional
    logFilePath: 'logs/bot.log' // Optional
);
```

**EnvLoader** - Object-oriented .env loader:

- Searches multiple paths
- Supports quoted/unquoted values
- Skips comments
- Sets `$_ENV`, `putenv()`, internal cache

---

## How to Work With This Codebase

### Where Logic Lives

**Service Layer** → Domain-specific business logic

- MessageService: Text formatting, auto-escaping
- MediaService: File handling
- ChatService: Chat administration
- WebhookService: Webhook management

**API Layer** → Pure HTTP orchestration

- No business logic
- Just URL construction and HTTP delegation

**Facade Layer** → Entry point and orchestration

- TelegramBot: Service accessors + convenience methods

### What to Avoid

❌ **Anti-Patterns:**

- Instantiating services directly (use TelegramBot)
- Bypassing ApiService in new service classes
- Hardcoding tokens or configuration
- Mixing responsibilities (service doing HTTP work)

✅ **Best Practices:**

- Use service accessors through TelegramBot facade
- Let service layer handle business logic
- Let API layer handle HTTP concerns
- Use formatters for manual styling (HTML over MarkdownV2)

### How to Extend Safely

**Adding New API Methods:**

1. Add enum value to `ApiMethod`:

```php
enum ApiMethod: string {
    case YOUR_NEW_METHOD = 'yourNewMethod';
}
```

1. Add method to appropriate service class:

```php
public function yourNewMethod(array $params): array
{
    return $this->apiService->call(ApiMethod::YOUR_NEW_METHOD, $params);
}
```

1. Add accessor to TelegramBot if needed (for frequently used methods)

**Adding New Services:**

1. Create service class in `src/Api/Methods/`:

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

1. Add service instance to TelegramBot constructor
2. Add accessor method if service is frequently accessed

**Using Custom HTTP Client:**

```php
$httpClient = new CustomHttpClient();
$config = new BotConfig(token: 'your_token');
$bot = new TelegramBot(null, $config, $httpClient);
```

---

## Code Conventions

### Naming

| Type       | Convention           |
| ---------- | -------------------- |
| Classes    | PascalCase           |
| Methods    | camelCase            |
| Properties | camelCase            |
| Constants  | SCREAMING_SNAKE_CASE |

### File Structure

- One class per file
- Filename matches class name
- Namespace matches directory structure
- `declare(strict_types=1);` at top of every file

### Type Annotations

Required for:

- All public methods
- Complex array shapes: `@param array<key, type>`
- Non-obvious return types

```php
/**
 * @param array<string, mixed> $params
 * @return array<string, mixed>
 */
public function sendMessage(array $params): array
{
    // Implementation
}
```

---

## Exception Handling

### Hierarchy

```
TelegramException (abstract)
├── ApiException          - Telegram API errors (4xx, 5xx)
└── HttpClientException   - HTTP layer errors (network, DNS, timeout)
```

### Usage

```php
try {
    $result = $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Hello']);
} catch (ApiException $e) {
    // Telegram API error
    error_log("API Error: {$e->getMessage()}");
} catch (HttpClientException $e) {
    // Network/HTTP error
    error_log("HTTP Error: {$e->getMessage()}");
} catch (TelegramException $e) {
    // Any framework error
    error_log("Framework Error: {$e->getMessage()}");
}
```

---

## Formatters

### MarkdownV2Formatter

**Auto-escape** is applied automatically by MessageService.

**Manual Formatting:**

```php
$formatter = new MarkdownV2Formatter();
$bold = $formatter->bold('Bold');
$italic = $formatter->italic('Italic');
```

### HtmlFormatter

**Does not auto-escape** - use for manual formatting to avoid conflicts.

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
    ->build();

$bot->sendMessage([
    'chat_id' => $chatId,
    'reply_markup' => $keyboard
]);
```

### ReplyKeyboardBuilder

```php
$keyboard = ReplyKeyboardBuilder::create()
    ->addButton('Option 1')
    ->addButton('Option 2')
    ->nextRow()
    ->addButton('Option 3')
    ->setOptions(
        ReplyKeyboardOptions::create()
            ->resize()
            ->oneTime()
    )
    ->build();
```

---

## Bulk Operations

### sendBulk

Send different messages to different chats:

```php
$messages = [
    ['chat_id' => 123, 'text' => 'Hello 1'],
    ['chat_id' => 456, 'text' => 'Hello 2'],
    ['chat_id' => 789, 'text' => 'Hello 3'],
];

$result = $bot->messages()->sendBulk($messages);
```

### broadcast

Send same message to multiple chats:

```php
$chatIds = [123, 456, 789];

$result = $bot->messages()->broadcast($chatIds, 'Announcement!');
```

---

## Webhooks

### Setup

```php
$bot = new TelegramBot();

$result = $bot->webhooks()->set([
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

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => "You said: $text"
    ]);
}
```

---

## Testing

### Run Tests

```bash
# All tests
vendor/bin/phpunit

# Specific suite
vendor/bin/phpunit tests/Unit/Api/
```

### Test Structure

```
tests/
├── Unit/           # Unit tests
├── Integration/    # Integration tests
├── Helpers/        # Test utilities
└── bootstrap.php   # Test bootstrap
```

---

## Critical Design Decisions

### Auto-Escape MarkdownV2

**Rationale:**

- MarkdownV2 has strict syntax requirements
- Unescaped characters cause API errors
- Auto-escaping provides better UX

**Trade-off:**

- Manual formatting requires using formatters
- Can bypass by using HTML parse mode

### Service-Oriented Architecture

**Benefits:**

- Clear separation of concerns
- Easy to test (services can be mocked)
- Easy to extend (add new services)

### Readonly Properties

**Benefits:**

- Immutability by default
- Thread-safe
- Self-documenting

### Enums

**Benefits:**

- Type safety (compile-time checking)
- IDE support (autocomplete, refactoring)
- No magic strings

---

## Performance Considerations

### Bulk Operations

- **Default max concurrent:** 30 requests
- **Adjust based on:** Server capacity, network, Telegram rate limits
- **Delay between batches:** Use `delay_ms` option

### HTTP Client Selection

- **CurlHttpClient:** Best performance, requires curl extension
- **StreamHttpClient:** Fallback, works everywhere

---

## Security Notes

### Token Handling

- **Never hardcode tokens** in code
- **Always use environment variables**
- `.env` file is in `.gitignore`

### Webhook Security

- **Use HTTPS** (required by Telegram)
- **Validate secret token** if configured
- **Implement rate limiting** if needed

---

## Contributing

### Before Submitting PR

1. **Run tests:** `vendor/bin/phpunit`
2. **Check code style:** Follow conventions above
3. **Update documentation:** If adding public API
4. **Add tests:** For new features
5. **Test examples:** Verify existing examples still work

### Commit Message Format

```
[FEATURE] Add new feature description
[REFACTOR] Refactor description
[BUGFIX] Fix bug description
```

Example:

```
[FEATURE] Add support for new Telegram API method

- Add newApiMethod enum value
- Implement in MessageService
- Add tests
- Update documentation
```

---

## Critical Files Reference

| File                                 | Purpose                   |
| ------------------------------------ | ------------------------- |
| `autoload.php`                       | PSR-4 autoloader          |
| `src/Bot/TelegramBot.php`            | Main facade               |
| `src/Config/EnvLoader.php`           | .env loader               |
| `src/Api/Methods/MessageService.php` | Message ops (auto-escape) |
| `src/Bulk/BulkOperationManager.php`  | Parallel requests         |
| `public/webhook.php`                 | Production endpoint       |

---

**Last Updated:** 2026-03-27  
**Framework Version:** 1.1  
**PHP Version:** 8.1+
