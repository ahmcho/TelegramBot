# AhmCho\Telegram Documentation

Welcome to the official documentation for **AhmCho\Telegram** — a modern,
dependency-free PHP 8.1+ framework for building Telegram bots with a clean,
service-oriented interface.

This documentation is written for developers who have **never seen this
framework before**. If you already know how to write a Telegram bot in PHP
but not with this library, start at [Quickstart](04-quickstart.md). If you're
new to Telegram bots in general, start at the beginning.

> New to the repository itself? The project root also has a top-level
> `README.md` (marketing pitch + feature list) and a `CLAUDE.md` (internal
> architecture notes for contributors/AI assistants). This `docs/` folder is
> the actual user manual — read this to learn how to *use* the framework.

---

## Table of Contents

### Getting Started

1. [Introduction](01-introduction.md) — what this framework is, why it exists, what problems it solves
2. [Installation](02-installation.md) — Composer, manual install, requirements, folder layout
3. [Configuration](03-configuration.md) — `BotConfig`, `.env`, immutable mutators
4. [Quickstart](04-quickstart.md) — your first bot in both long-polling and webhook mode

### Core Concepts

5. [The Bot Facade](05-the-bot-facade.md) — `TelegramBot`, `BotFactory`, service accessors
6. [Sending Messages](06-sending-messages.md) — `MessageService` in depth
7. [Formatting Text](07-formatting-text.md) — MarkdownV2 / HTML, auto-escaping, `ParseMode`
8. [Keyboards](08-keyboards.md) — inline keyboards, reply keyboards, buttons

### Working With Telegram Features

9. [Media & Files](09-media-and-files.md) — photos, documents, albums, downloads
10. [Commands](10-commands.md) — the built-in command router and middleware
11. [Webhooks](11-webhooks.md) — receiving updates over HTTP, securing the endpoint
12. [Chats & Administration](12-chats-and-administration.md) — chat info, permissions, bans, menu buttons
13. [Polls, Inline Mode & Topics](13-polls-inline-topics.md) — `PollsService`, `InlineService`, `TopicsService`
14. [Invite Links](14-invite-links.md) — creating and managing chat invite links
15. [Games & Payments](15-games-and-payments.md) — `GamesService`, `PaymentsService`

### Advanced

16. [Bulk Operations & Broadcasting](16-bulk-operations.md) — sending to many chats in parallel
17. [Retry & Resilience](17-retry-and-resilience.md) — automatic retry with backoff
18. [Error Handling](18-error-handling.md) — the exception hierarchy and how to catch it
19. [Logging](19-logging.md) — PSR-3 logging, log levels, rotation
20. [HTTP Clients](20-http-clients.md) — cURL vs stream transport, writing your own client

### Digging Deeper

21. [Testing](21-testing.md) — `MockHttpClient` and how to test bots built on this framework
22. [Cookbook](22-cookbook.md) — end-to-end recipes: album uploads, broadcasts, forum bots, and more

---

## How to read this

Every page in this doc set is self-contained but builds on the ones before
it. Each page ends with **Previous / Next** links so you can read straight
through like a book, or jump directly to the topic you need from the table
of contents above.

All code examples are copy-pasteable and use the real, current public API of
this framework — nothing hypothetical or "coming soon."

Ready? Start with [Introduction →](01-introduction.md)
