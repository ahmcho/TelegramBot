# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Command handler system with middleware support
- Automatic retry logic with exponential backoff
- Rate limit (429) detection and handling
- Retry methods: `sendMessageWithRetry()`, `sendBulkWithRetry()`, `executeWithRetry()`
- CommandHandler class with command registration, help generation, and middleware
- `retry-demo.php` example demonstrating retry functionality
- `commands-demo.php` example demonstrating command system
- Comprehensive README documentation
- Examples update to use modern EnvLoader

### Changed
- Updated all example files to use `EnvLoader` instead of manual `.env` parsing
- SSL verification disabled by default in `CurlHttpClient`
- Improved error handling with null-safe file ID extraction
- Refactored code to use modern PHP 8.3+ features

### Fixed
- SSL certificate errors with self-signed certificates
- Null-safe file ID extraction in media handlers
- Inconsistent environment loading across examples
- Duplicate environment loading code
- Duplicate MarkdownV2 escaping code

### Removed
- Broken `commands.php` example file

## [1.1.0] - 2026-06-27

### Added
- PHP 8.1+ support with modern features
- Service-oriented architecture
- Auto-escaping for MarkdownV2 parse mode
- Bulk operations with parallel requests
- PSR-3 logging support
- Type safety with strict types and readonly properties

### Changed
- Migrated from procedural to OOP architecture
- Introduced service layer (MessageService, MediaService, ChatService, WebhookService)
- Added facade pattern with TelegramBot class

### Removed
- Database support (user storage is now user's responsibility)

## [1.0.0] - Initial Release

### Added
- Basic Telegram Bot API wrapper
- Long polling support
- Webhook support
- Message sending and receiving
- Media file handling
- Inline and reply keyboards
- Basic error handling

---

## Versioning Scheme

- **Major (X.0.0)**: Breaking changes, significant new features
- **Minor (x.X.0)**: New features, backwards compatible
- **Patch (x.x.X)**: Bug fixes, minor improvements

---

## Migration Guides

### From 1.0 to 1.1

**Breaking Changes:**
- Database support removed - implement your own user storage
- Constructor signatures changed - use service accessors instead

**New Features:**
- Use `$bot->messages()` instead of direct method calls
- Auto-escaping enabled by default for MarkdownV2
- Use `sendRaw()` methods to preserve formatting

**Migration Example:**

```php
// Old way
$bot = new TelegramBot($token);
$bot->sendMessage(['chat_id' => $chatId, 'text' => $text]);

// New way
$bot = new TelegramBot();
$bot->messages()->send(['chat_id' => $chatId, 'text' => $text]);
```

### From 1.1 to 1.2

**New Features:**
- Command handler system for easy command routing
- Automatic retry with exponential backoff
- Rate limit handling

**Optional Migration:**

```php
// Optional: Use new command handler
$bot->commands()
    ->register('start', fn($bot, $chatId, $args) => ...)
    ->register('help', fn($bot, $chatId, $args) => ...);

// Optional: Use retry for important messages
$result = $bot->sendMessageWithRetry($params);
```
