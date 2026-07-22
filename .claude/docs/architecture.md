# Architecture

[← Back to CLAUDE.md](../../CLAUDE.md)

## Layers

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

## Design Patterns

| Pattern       | Location                             | Purpose                      |
| ------------- | ------------------------------------ | ---------------------------- |
| Facade        | `src/Bot/TelegramBot.php`            | Unified interface            |
| Factory       | `src/Bot/BotFactory.php`             | Pre-configured instances     |
| Builder       | `src/Keyboard/*Builder.php`          | Fluent keyboard construction |
| Service Layer | `src/Api/Methods/`                   | Domain-specific operations   |
| Strategy      | `src/Client/HttpClientInterface.php` | Swappable HTTP clients       |

## Directory Structure

```
tg-bots/
├── autoload.php
├── CLAUDE.md
├── README.md
├── .env.example
├── .claude/docs/       # This reference set (see CLAUDE.md)
├── docs/               # User-facing documentation (see docs/README.md)
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
│   │   └── Traits/
│   │       ├── ResponseParserTrait.php
│   │       └── MultipartRequestTrait.php
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

## Critical Files Reference

| File                                        | Purpose                             |
| -------------------------------------------- | ------------------------------------ |
| `autoload.php`                              | PSR-4 autoloader                     |
| `src/Bot/TelegramBot.php`                   | Main facade (final)                  |
| `src/Bot/BotFactory.php`                    | Static construction helpers          |
| `src/Config/BotConfig.php`                  | Immutable configuration              |
| `src/Config/EnvLoader.php`                  | .env loader                          |
| `src/dotenv.php`                            | Auto-load .env shortcut              |
| `src/Api/Methods/MessageService.php`        | Message ops + auto-escape            |
| `src/Api/Methods/MediaService.php`          | Media ops + caption auto-escape      |
| `src/Bulk/BulkOperationManager.php`         | Parallel requests via curl_multi     |
| `src/Bulk/BulkResult.php`                   | Typed bulk result value object       |
| `src/Command/CommandHandler.php`            | Command routing                      |
| `src/Logging/LoggerFactory.php`             | Logger creation                      |
| `src/Traits/MarkdownV2EscapeTrait.php`      | Auto-escape logic used by services   |
| `src/Logging/Traits/LoggerHelperTrait.php`  | Null-safe logging helpers            |
| `public/webhook.php`                        | Production webhook endpoint          |
