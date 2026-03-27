# CLAUDE.md - Developer & AI Assistant Guide

## Purpose & Scope

This document provides comprehensive context for AI assistants and developers working on the **AhmCho\Telegram** framework. It complements the user-facing [README.md](README.md) by explaining the architecture, design patterns, and implementation details that enable effective contributions to the codebase.

## What is This Framework?

**AhmCho\Telegram** is a modern, dependency-free PHP 8.1+ Telegram Bot Framework that provides a clean, service-oriented interface to the Telegram Bot API. It requires zero external dependencies for core functionality, making it lightweight and easy to deploy.

## 📌 Important Notice

**Database support has been removed from this framework as of version 1.1**

The framework now focuses solely on Telegram Bot API logic. If you need user storage, please implement your own solution using your preferred database and persistence layer.

### What Changed?

To follow the Single Responsibility Principle and provide a more focused, lightweight framework:
- ❌ No database dependencies
- ✅ Simpler architecture
- ✅ Easier to integrate with your infrastructure
- ✅ More predictable behavior

### What's Still Included?

All Telegram Bot API features remain fully functional:
- ✅ Message handling and formatting
- ✅ Media sending (photos, videos, audio, etc.)
- ✅ Chat administration
- ✅ Webhook support
- ✅ Bulk messaging (with your own user lists)
- ✅ Inline and reply keyboards
- ✅ Auto-escaping for MarkdownV2
- ✅ Comprehensive logging

### Managing Users Yourself?

You have full control over user storage:

```php
// Extract user from update
$update = $bot->getWebhookUpdates();
$userId = $update['message']['from']['id'];
$username = $update['message']['from']['username'];

// Store in your preferred database
// (PostgreSQL, MySQL, MongoDB, Redis, etc.)

// Send bulk messages to your user list
$userIds = [123, 456, 789]; // From your database
$bot->messages()->sendBulk([
    ['chat_id' => 123, 'text' => 'Hello!'],
    ['chat_id' => 456, 'text' => 'Hello!'],
    ['chat_id' => 789, 'text' => 'Hello!']
]);
```

### Key Design Philosophy

- **Zero runtime dependencies**: Only requires PHP 8.1+ extensions (curl, json, mbstring, openssl, fileinfo)
- **Modern PHP patterns**: Uses strict types, readonly properties, enums, and PSR-4 autoloading
- **Service-oriented architecture**: Clean separation of concerns with dedicated service classes
- **Developer experience first**: Fluent interfaces, auto-escaping, and comprehensive error handling

## Architecture Overview

### Layered Design

```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
│                  (User Code / Examples)                      │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                   Facade Layer                               │
│            TelegramBot (Main Entry Point)                    │
│         - Service Accessors                                  │
│         - Convenience Methods                                │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                  Service Layer                               │
│  ┌──────────────┬──────────────┬──────────────┬───────────┐ │
│  │ MessageService│ MediaService │ ChatService  │WebhookSvc│ │
│  └──────────────┴──────────────┴──────────────┴───────────┘ │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                   API Layer                                  │
│              ApiService (Orchestrator)                       │
│         - Method Routing                                     │
│         - Bulk Operation Management                          │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│                  Client Layer                                │
│         HttpClientInterface (Abstraction)                    │
│    ┌─────────────────┬──────────────────────┐              │
│    │ CurlHttpClient  │  StreamHttpClient    │              │
│    └─────────────────┴──────────────────────┘              │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│              Infrastructure Layer                            │
│  - Config (BotConfig, EnvLoader)                            │
│  - Enums (ApiMethod, HttpMethod, etc.)                      │
│  - Exception Hierarchy                                      │
└─────────────────────────────────────────────────────────────┘
```

### Design Patterns Used

| Pattern | Usage | Location |
|---------|-------|----------|
| **Facade** | TelegramBot provides unified interface | `src/Bot/TelegramBot.php` |
| **Factory** | Creates configured bot instances | `src/Bot/BotFactory.php` |
| **Builder** | Fluent keyboard construction | `src/Keyboard/*KeyboardBuilder.php` |
| **Service Layer** | Domain-specific API operations | `src/Api/Methods/*Service.php` |
| **Strategy** | Swappable HTTP clients | `src/Client/HttpClientInterface.php` |

## Directory Structure

```
tg-bots/
├── autoload.php                 # PSR-4 autoloader (standalone)
├── composer.json                # Composer configuration (dev deps only)
├── CLAUDE.md                    # This file - developer/AI guide
├── README.md                    # User-facing documentation
├── .env.example                 # Environment template
│
├── src/                         # Framework source code
│   ├── Api/                     # API services
│   │   ├── ApiService.php       # Core API orchestrator
│   │   └── Methods/             # Domain-specific services
│   │       ├── MessageService.php    # Message operations
│   │       ├── MediaService.php      # Media handling
│   │       ├── ChatService.php       # Chat administration
│   │       └── WebhookService.php    # Webhook management
│   │
│   ├── Bot/                     # Bot management
│   │   ├── TelegramBot.php      # Main facade class
│   │   └── BotFactory.php       # Factory for bot creation
│   │
│   ├── Bulk/                    # Bulk operations
│   │   ├── BulkOperationManager.php  # Parallel request handling
│   │   ├── BulkResult.php            # Result aggregation
│   │   └── BulkSendException.php     # Bulk operation errors
│   │
│   ├── Client/                  # HTTP client abstraction
│   │   ├── HttpClientInterface.php   # Client contract
│   │   ├── HttpClientFactory.php     # Client factory
│   │   ├── CurlHttpClient.php        # cURL implementation
│   │   └── StreamHttpClient.php      # Stream wrapper implementation
│   │
│   ├── Config/                  # Configuration
│   │   ├── BotConfig.php             # Type-safe configuration
│   │   └── EnvLoader.php             # .env file loader
│   │
│   ├── Enums/                   # Type-safe enums
│   │   ├── ApiMethod.php             # Telegram API methods
│   │   ├── ChatAction.php            # Chat action types
│   │   ├── HttpMethod.php            # HTTP methods
│   │   └── ParseMode.php             # Parse mode types
│   │
│   ├── Exception/               # Exception hierarchy
│   │   ├── TelegramException.php     # Base exception
│   │   ├── ApiException.php          # Telegram API errors
│   │   └── HttpClientException.php   # HTTP layer errors
│   │
│   ├── Formatting/              # Text formatting
│   │   ├── TextFormatterInterface.php
│   │   ├── HtmlFormatter.php
│   │   └── MarkdownV2Formatter.php    # Auto-escaping formatter
│   │
│   └── Keyboard/                # Keyboard builders
│       ├── KeyboardBuilderInterface.php
│       ├── InlineKeyboardBuilder.php
│       ├── ReplyKeyboardBuilder.php
│       ├── Button.php
│       └── ReplyKeyboardOptions.php
│
├── public/                      # Public-facing files
│   └── webhook.php              # Production webhook endpoint
│
├── examples/                    # Usage examples
│   ├── echo.php                 # Simple echo bot
│   ├── commands.php             # Command handling
│   ├── menu.php                 # Keyboard navigation
│   └── bulk-test.php            # Bulk messaging demo
│
├── tests/                       # Test suite
│   ├── Unit/                    # Unit tests
│   ├── Integration/             # Integration tests
│   ├── Helpers/                 # Test utilities
│   └── bootstrap.php            # Test bootstrap
│
```

## Key Components Deep Dive

### 1. Core Classes

#### TelegramBot (`src/Bot/TelegramBot.php`)

**Main facade class** providing unified access to all framework functionality.

**Key Responsibilities:**
- Service accessor methods (`messages()`, `media()`, `chats()`, `webhooks()`)
- Webhook handling (`getWebhookUpdates()`, `processWebhook()`)
- Backward compatibility methods (`sendMessage()`, `sendPhoto()`)

**Usage Pattern:**
```php
use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot();

// Access services
$bot->messages()->send(['chat_id' => 123, 'text' => 'Hello']);
$bot->media()->sendPhoto(['chat_id' => 123, 'photo' => '...']);
```

**Critical Implementation Detail:**
- Constructor automatically loads environment variables via `EnvLoader`
- Token can be provided directly or loaded from `TELEGRAM_BOT_TOKEN` env var
- All services are instantiated once and cached in readonly properties

#### BotFactory (`src/Bot/BotFactory.php`)

**Factory pattern** for creating pre-configured bot instances.

**Factory Methods:**
```php
// Simple creation (token from env)
BotFactory::create();

// With explicit token
BotFactory::create($token);

// With custom config
BotFactory::createWithConfig($config);

// With custom HTTP client
BotFactory::createWithHttpClient($token, $httpClient);
```

#### ApiService (`src/Api/ApiService.php`)

**Core orchestrator** for all Telegram API calls.

**Responsibilities:**
- Constructs full API URLs from bot token
- Delegates to HTTP client for actual requests
- Provides access to BulkOperationManager
- Manages BotConfig instance

**Design Note:** This is a thin orchestrator layer. Business logic lives in service classes.

### 2. Service Layer (`src/Api/Methods/`)

#### MessageService

**Message operations with auto-escaping for MarkdownV2.**

**Critical Feature:** Automatically escapes text when `parse_mode => 'MarkdownV2'` is set. This prevents Telegram API errors caused by unescaped special characters.

**Auto-Escape Logic:**
```php
private function escapeForMarkdownV2(array $params): array
{
    if (!isset($params['parse_mode']) || $params['parse_mode'] !== 'MarkdownV2') {
        return $params; // Only escape MarkdownV2
    }

    $formatter = new MarkdownV2Formatter();

    if (isset($params['text']) && is_string($params['text'])) {
        $params['text'] = $formatter->escape($params['text']);
    }

    if (isset($params['caption']) && is_string($params['caption'])) {
        $params['caption'] = $formatter->escape($params['caption']);
    }

    return $params;
}
```

**Why This Matters:**
- MarkdownV2 has strict syntax requirements
- Unescaped characters like `_*[]()~`>#+-=|{}.!` cause API errors
- Auto-escaping provides better UX while allowing manual formatting via formatters

**Methods:**
- `send()` - Send message with auto-escape
- `editText()` - Edit message text with auto-escape
- `editCaption()` - Edit caption with auto-escape
- `delete()` - Delete message
- `forward()` - Forward message
- `copy()` - Copy message
- `sendBulk()` - Bulk send with auto-escape
- `broadcast()` - Broadcast with auto-escape

#### MediaService

**Media file handling (photos, videos, audio, etc.)**

Supports three file input types:
1. **File ID** - String from previous uploads
2. **URL** - Telegram downloads the file
3. **CURLFile** - Local file upload

#### ChatService

**Chat and member administration**

Methods for:
- Member management (ban, unban, kick, restrict, promote)
- Chat settings (title, description, photo, permissions)
- Member information retrieval

#### WebhookService

**Webhook management**

Methods:
- `set()` - Set webhook URL
- `getInfo()` - Get current webhook info
- `delete()` - Remove webhook

### 3. Bulk Operations (`src/Bulk/`)

**Parallel request execution using `curl_multi_exec`.**

#### Architecture

```
BulkOperationManager
    ├── sendBulk() - Execute multiple different requests
    └── broadcast() - Send same request to multiple chats

HttpClientInterface::requestMulti()
    ├── Splits requests into batches
    ├── Uses curl_multi_exec for parallel execution
    └── Returns aggregated results
```

#### BulkResult Structure

```php
BulkResult {
    success: bool,          // true if all succeeded
    total: int,             // Total attempts
    successful: int,        // Success count
    failed: int,            // Failure count
    results: [              // Individual results
        [
            'success' => bool,
            'chat_id' => int|string,
            'message_id' => int|null,
            'data' => array|null,      // API response
            'error' => string|null     // Error message
        ]
    ]
}
```

#### Configuration Options

```php
$options = [
    'max_concurrent' => 30,  // Max parallel requests (default: 30)
    'delay_ms' => 0          // Delay between batches (default: 0)
];
```

#### Usage Example

```php
$bot = new TelegramBot();

// Send different messages
$messages = [
    ['chat_id' => 123, 'text' => 'Hello'],
    ['chat_id' => 456, 'text' => 'World'],
];

$result = $bot->messages()->sendBulk($messages);

// Broadcast same message
$chatIds = [123, 456, 789];
$result = $bot->messages()->broadcast($chatIds, 'Announcement!');

// Check results
foreach ($result->results as $r) {
    if (!$r['success']) {
        echo "Failed for {$r['chat_id']}: {$r['error']}\n";
    }
}
```

### 4. Configuration (`src/Config/`)

#### BotConfig

**Immutable configuration object with readonly properties.**

```php
$config = new BotConfig(
    token: '123:ABC',
    apiUrl: 'https://api.telegram.org/bot',  // Optional, default provided
    throwExceptions: true                     // Optional, default true
);
```

#### EnvLoader

**Object-oriented .env file loader.**

**Features:**
- Searches multiple paths for `.env` file
- Supports quoted and unquoted values
- Skips comments (lines starting with `#`)
- Sets `$_ENV`, `putenv()`, and internal cache

**Search Path Order:**
1. `getcwd()/.env`
2. `dirname(__DIR__)/.env`
3. `__DIR__ . '/../../.env'`

**Usage:**
```php
$loader = new EnvLoader();
$loader->load();

$token = $loader->require('TELEGRAM_BOT_TOKEN');
$value = $loader->get('OPTIONAL_VAR', 'default');
```

### 6. HTTP Client Layer (`src/Client/`)

**Abstraction for multiple HTTP implementations.**

#### HttpClientInterface

```php
interface HttpClientInterface {
    public function request(HttpMethod $method, string $url, array $params): mixed;
    public function requestMulti(HttpMethod $method, string $url, array $requests, array $options): array;
}
```

#### Implementations

**CurlHttpClient** (default)
- Uses `curl_exec` for single requests
- Uses `curl_multi_exec` for bulk operations
- Best performance for production

**StreamHttpClient**
- Uses `file_get_contents` with stream context
- Fallback when cURL is unavailable
- Simpler but slower

#### Factory Pattern

```php
$httpClient = HttpClientFactory::create($config);
// Automatically selects best available client
```

### 7. Enums (`src/Enums/`)

**Type-safe, backed enums for API constants.**

#### ApiMethod

```php
enum ApiMethod: string {
    case GET_ME = 'getMe';
    case SEND_MESSAGE = 'sendMessage';
    case GET_UPDATES = 'getUpdates';
    // ... all Telegram API methods
}
```

**Benefits:**
- IDE autocomplete support
- Compile-time checking
- Refactoring safety
- Self-documenting code

#### Other Enums

- `ChatAction` - `typing`, `upload_photo`, etc.
- `HttpMethod` - `GET`, `POST`
- `ParseMode` - `Markdown`, `MarkdownV2`, `HTML`

### 8. Formatters (`src/Formatting/`)

**Text formatting helpers for styled messages.**

#### MarkdownV2Formatter

```php
$formatter = new MarkdownV2Formatter();

// Escape all special characters
$escaped = $formatter->escape('Hello_World'); // 'Hello\_World'

// Apply formatting manually
$bold = $formatter->bold('Bold text');
$italic = $formatter->italic('Italic text');
```

**Special Characters Escaped:**
```
_ * [ ] ( ) ~ ` > # + - = | { } . !
```

#### HtmlFormatter

```php
$formatter = new HtmlFormatter();

$bold = $formatter->bold('Bold text'); // '<b>Bold text</b>'
$link = $formatter->link('Click here', 'https://example.com');
```

### 9. Keyboard Builders (`src/Keyboard/`)

**Fluent interfaces for keyboard construction.**

#### InlineKeyboardBuilder

```php
$keyboard = InlineKeyboardBuilder::create()
    ->addButton('Button 1', 'callback_data_1')
    ->addButton('Button 2', 'callback_data_2')
    ->addUrlButton('Open Google', 'https://google.com')
    ->nextRow()
    ->addButton('Button 3', 'callback_data_3')
    ->build();

$bot->sendMessage([
    'chat_id' => $chatId,
    'text' => 'Choose:',
    'reply_markup' => $keyboard
]);
```

#### ReplyKeyboardBuilder

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

## Code Conventions & Patterns

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `TelegramBot`, `MessageService` |
| Methods | camelCase | `sendMessage`, `getWebhookInfo` |
| Properties | camelCase | `$userRepository`, `$apiService` |
| Constants | SCREAMING_SNAKE_CASE | `DEFAULT_TIMEOUT`, `MAX_RETRIES` |

### File Structure Conventions

1. **One class per file**
2. **Filename matches class name**
3. **Namespace matches directory structure**
4. **All files start with `declare(strict_types=1);`**

### Type Annotations

**All methods must have full type annotations:**

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

**PHPDoc blocks required for:**
- All public methods
- Complex array shapes (use `@param array<key, type>`)
- Return types that aren't obvious

### Code Style Checklist

- [ ] `declare(strict_types=1);` at top
- [ ] No trailing whitespace
- [ ] 4 spaces for indentation (no tabs)
- [ ] Opening brace on same line for classes/methods
- [ ] Visibility declared on all properties/methods
- [ ] `readonly` for immutable properties
- [ ] Type hints on all parameters and return types

## Development Guidelines

### Adding New Features

#### 1. Adding a New API Method

**Step 1:** Add enum value to `src/Enums/ApiMethod.php`
```php
case YOUR_NEW_METHOD = 'yourNewMethod';
```

**Step 2:** Add method to appropriate service class in `src/Api/Methods/`
```php
public function yourNewMethod(array $params): array
{
    return $this->apiService->call(ApiMethod::YOUR_NEW_METHOD, $params);
}
```

**Step 3:** Add accessor to `TelegramBot` if needed (or use service directly)

**Step 4:** Write tests in `tests/Unit/Api/*ServiceTest.php`

#### 2. Creating a New Service

**Step 1:** Create service class in `src/Api/Methods/YourDomainService.php`
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

**Step 2:** Add service to `TelegramBot` class
```php
private readonly YourDomainService $yourDomain;

// In constructor:
$this->yourDomain = new YourDomainService($this->apiService);

// Add accessor:
public function yourDomain(): YourDomainService
{
    return $this->yourDomain;
}
```

**Step 3:** Write tests

### Working with Services

**Always access services through the facade:**

```php
$bot = new TelegramBot();

// ✅ Correct - Use service accessor
$bot->messages()->send([...]);
$bot->media()->sendPhoto([...]);

// ❌ Avoid - Instantiate services directly
$apiService = new ApiService(...); // Don't do this
```

**Why:**
- Consistent interface
- Configuration is centralized
- Easier testing and mocking

### Error Handling

**Exception Hierarchy:**
```
TelegramException (abstract)
├── ApiException          - Telegram API errors (4xx, 5xx)
└── HttpClientException   - HTTP layer errors (network, DNS, timeout)
```

**Best Practices:**

```php
try {
    $result = $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Hello']);
} catch (ApiException $e) {
    // Telegram API returned an error
    error_log("API Error: {$e->getMessage()}");
} catch (HttpClientException $e) {
    // Network/HTTP error
    error_log("HTTP Error: {$e->getMessage()}");
} catch (TelegramException $e) {
    // Any framework error
    error_log("Framework Error: {$e->getMessage()}");
}
```

**Individual Bulk Errors:**
```php
$result = $bot->messages()->sendBulk($messages);

// Check individual failures
foreach ($result->results as $r) {
    if (!$r['success']) {
        error_log("Failed for {$r['chat_id']}: {$r['error']}");
    }
}
```

## Testing Strategy

### Test Structure

```
tests/
├── Unit/                      # Unit tests (no external dependencies)
│   ├── Api/                   # Service layer tests
│   ├── Bot/                   # Facade tests
│   ├── Bulk/                  # Bulk operation tests
│   ├── Config/                # Configuration tests
│   ├── Formatting/            # Formatter tests
│   ├── Keyboard/              # Builder tests
│   └── Exception/             # Exception tests
│
├── Integration/               # Integration tests (real API calls)
│   └── TelegramApiIntegrationTest.php
│
├── Helpers/                   # Test utilities
│   ├── MockHttpClient.php     # Mock HTTP client
│   ├── MockTelegramResponse.php
│   ├── TestDataFactory.php    # Test data generation
│   └── WebhookStreamWrapper.php
│
└── bootstrap.php              # Test bootstrap file
```

### Running Tests

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/Unit/Api/

# Run with coverage
vendor/bin/phpunit --coverage-html coverage/
```

### Writing Unit Tests

**Example Test:**
```php
<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Api;

use AhmCho\Telegram\Api\ApiService;
use AhmCho\Telegram\Api\Methods\MessageService;
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Enums\ApiMethod;
use PHPUnit\Framework\TestCase;

class MessageServiceTest extends TestCase
{
    private HttpClientInterface $mockClient;
    private ApiService $apiService;
    private MessageService $messageService;

    protected function setUp(): void
    {
        $this->mockClient = $this->createMock(HttpClientInterface::class);
        $config = new BotConfig(token: 'test:token');
        $this->apiService = new ApiService($this->mockClient, $config, $bulkManager);
        $this->messageService = new MessageService($this->apiService);
    }

    public function testSendEscapesMarkdownV2(): void
    {
        $this->mockClient
            ->expects($this->once())
            ->method('request')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->callback(function ($params) {
                    return $params['text'] === 'Hello\_World';
                })
            )
            ->willReturn(['ok' => true, 'result' => ['message_id' => 1]]);

        $result = $this->messageService->send([
            'chat_id' => 123,
            'text' => 'Hello_World',
            'parse_mode' => 'MarkdownV2'
        ]);

        $this->assertEquals(1, $result['message_id']);
    }
}
```

### Test Helpers

**MockHttpClient:**
```php
$client = new MockHttpClient();
$client->setResponse(['ok' => true, 'result' => [...]]);

$apiService = new ApiService($client, $config, $bulkManager);
```

## Common Tasks Reference

### Running Examples

**Set token first:**
```bash
export TELEGRAM_BOT_TOKEN='your_token_here'
```

**Run example:**
```bash
php examples/echo.php
php examples/bulk-test.php
```

### Setting Up Webhook

```php
$bot = new TelegramBot();

$result = $bot->webhooks()->set([
    'url' => 'https://your-domain.com/public/webhook.php',
    'secret_token' => 'your_secret_token'
]);

print_r($result);
```

### Manual Text Formatting

**When auto-escaping interferes with manual formatting:**

```php
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;

$formatter = new MarkdownV2Formatter();

// Manually format text (don't use auto-escape)
$text = $formatter->bold('Bold') . ' and ' . $formatter->italic('italic');

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => 'MarkdownV2',
    'escape' => false  // Note: This flag doesn't exist, use formatter directly
]);

// Instead, use HtmlFormatter to avoid conflicts:
use AhmCho\Telegram\Formatting\HtmlFormatter;

$htmlFormatter = new HtmlFormatter();
$text = $htmlFormatter->bold('Bold') . ' and ' . $htmlFormatter->italic('italic');

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => 'HTML'  // HTML doesn't auto-escape
]);
```

### Custom HTTP Client

```php
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Bot\TelegramBot;

class CustomHttpClient implements HttpClientInterface
{
    public function request(HttpMethod $method, string $url, array $params): mixed
    {
        // Custom implementation
    }

    public function requestMulti(HttpMethod $method, string $url, array $requests, array $options): array
    {
        // Custom bulk implementation
    }
}

$customClient = new CustomHttpClient();
$config = new BotConfig(token: 'your_token');

$bot = new TelegramBot(null, $config, $customClient);
```

## Critical Files Quick Reference

| File | Purpose | Key Points |
|------|---------|------------|
| `autoload.php` | PSR-4 autoloader | Use this or `vendor/autoload.php` |
| `src/Bot/TelegramBot.php` | Main facade | Entry point for all operations |
| `src/Config/EnvLoader.php` | .env loader | Auto-loads in TelegramBot constructor |
| `src/Bulk/BulkOperationManager.php` | Parallel requests | Uses curl_multi_exec |
| `src/Api/Methods/MessageService.php` | Message ops | Auto-escapes MarkdownV2 |
| `public/webhook.php` | Production endpoint | Deploy this for webhooks |

## Troubleshooting for Developers

### Auto-Escaping Issues

**Problem:** Manual formatting doesn't work with MarkdownV2

**Cause:** MessageService auto-escapes text when using MarkdownV2

**Solutions:**
1. Use HTML parse mode instead
2. Use formatters (HtmlFormatter, MarkdownV2Formatter) for styling
3. Don't mix manual formatting with auto-escape

### Test Failures

**Common Issues:**
1. Missing `TELEGRAM_BOT_TOKEN` in environment
2. SQLite extension not enabled
3. Mock not properly configured
4. Test data factory creating invalid data

**Debug Steps:**
1. Run specific test with verbose output: `vendor/bin/phpunit --testdox tests/Unit/Api/MessageServiceTest.php`
2. Check test bootstrap file loads correctly
3. Verify mocks are configured correctly

## Design Decisions & Rationale

### Why Service-Oriented Architecture?

**Benefits:**
- Clear separation of concerns
- Easy to test (services can be mocked)
- Easy to extend (add new services without changing existing code)
- Follows Single Responsibility Principle

### Why Readonly Properties?

**Benefits:**
- Immutability by default
- Thread-safe
- Self-documenting (can't change after construction)
- Reduces cognitive load

### Why Enum-Backed Types?

**Benefits:**
- Type safety (compile-time checking)
- IDE support (autocomplete, refactoring)
- No magic strings
- Self-documenting code

### Why Auto-Escape MarkdownV2?

**Rationale:**
- MarkdownV2 has strict syntax
- Most users don't need manual formatting
- Prevents common API errors
- Can be bypassed by using HTML or formatters

**Trade-off:**
- Manual formatting requires using formatters
- This is intentional - better to be safe by default

## Performance Considerations

### Bulk Operations

- **Default max concurrent:** 30 requests
- **Adjust based on:** Server capacity, network, Telegram rate limits
- **Delay between batches:** Use `delay_ms` option for rate limiting

### HTTP Client Selection

- **CurlHttpClient:** Best performance, requires curl extension
- **StreamHttpClient:** Fallback, slower but works everywhere

## Security Notes

### Token Handling

- **Never hardcode tokens** in code
- **Always use environment variables**
- `.env` file is in `.gitignore`
- Use `secret_token` for webhook validation

### SQL Injection

- **Always use prepared statements** (handled by PDO)
- Never concatenate user input into SQL

### XSS Prevention

- Telegram handles this server-side
- No HTML/JS execution in messages
- Formatters escape as needed

## Contributing Guidelines

### Before Submitting PR

1. **Run tests:** `vendor/bin/phpunit`
2. **Check code style:** Follow conventions above
3. **Update documentation:** If adding public API
4. **Add tests:** For new features
5. **Test examples:** Verify existing examples still work

### Commit Message Format

```
[FEATURE] Add new feature description
[BUGFIX] Fix bug description
[REFACTOR] Refactor description
[DOCS] Documentation update
[TEST] Add/fix tests
```

Example:
```
[FEATURE] Add support for new Telegram API method

- Add newApiMethod enum value
- Implement in NewDomainService
- Add tests
- Update documentation
```

## External References

- [Telegram Bot API Documentation](https://core.telegram.org/bots/api)
- [Telegram Bot API Updates](https://core.telegram.org/bots/api#update)
- [PSR-4 Autoloading Standard](https://www.php-fig.org/psr/psr-4/)
- [PHP 8.1 Features](https://www.php.net/releases/8.1)
- [PHPUnit Documentation](https://phpunit.de/documentation.html)

---

**Last Updated:** 2026-03-27
**Framework Version:** 1.1
**PHP Version:** 8.1+
