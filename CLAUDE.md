# CLAUDE.md - Developer & AI Assistant Guide

## What is This Framework?

Modern, dependency-free PHP 8.1+ Telegram Bot Framework with a clean, service-oriented interface. Zero external dependencies required.

Namespace root: `AhmCho\Telegram`

Everything goes through the `TelegramBot` facade (`src/Bot/TelegramBot.php`), which exposes one accessor method per service (`messages()`, `media()`, `chats()`, `webhooks()`, `commands()`, etc). Don't instantiate service classes directly.

## Reference Docs

This file stays short on purpose. Detailed reference material lives in `.claude/docs/`:

| Doc | Covers |
| --- | --- |
| [.claude/docs/architecture.md](.claude/docs/architecture.md) | Layer diagram, design patterns, full directory tree, critical-files table |
| [.claude/docs/api-reference.md](.claude/docs/api-reference.md) | Every service class and its methods (Message, Media, Chat, Polls, Inline, InviteLinks, Topics, Webhook, Games, Payments, CommandHandler, ApiService), Bulk, Configuration, Logging, Exceptions |
| [.claude/docs/formatting-and-keyboards.md](.claude/docs/formatting-and-keyboards.md) | `TextFormatterInterface`, MarkdownV2Formatter, HtmlFormatter, InlineKeyboardBuilder, ReplyKeyboardBuilder |
| [.claude/docs/webhooks-and-bulk.md](.claude/docs/webhooks-and-bulk.md) | Webhook setup/handling, CommandHandler usage, sendBulk/broadcast examples, running tests |
| [.claude/docs/extending.md](.claude/docs/extending.md) | Where logic belongs, what to avoid, how to add API methods/services safely, code conventions |

Read the relevant doc before making a change in that area — don't guess method signatures from memory.

## User-Facing Docs (`docs/`)

`docs/` is a separate, Laravel-style documentation set for people *using* this framework (not contributors working on internals). It's 22 numbered pages plus `docs/README.md` as the table-of-contents hub, each with Previous/Next navigation, covering installation through the cookbook.

**When you change public behavior, update the matching `docs/` page in the same commit.** A new service needs a new `docs/NN-topic.md` page linked into `docs/README.md`'s table of contents and wired into the Previous/Next chain of its neighbors; a changed default or signature needs the matching page's examples corrected. Stale user-facing docs are worse than none — they actively mislead.

## Quick Orientation

```
src/Bot/TelegramBot.php       Facade — start here
src/Api/Methods/              One service class per Telegram feature area
src/Bulk/                     Parallel-request bulk sending
src/Client/                   HTTP client implementations (curl / stream)
src/Config/                   BotConfig (immutable) + .env loading
src/Logging/                  PSR-3 logging, null-safe throughout
src/Keyboard/                 Inline & reply keyboard builders
src/Formatting/               MarkdownV2 / HTML text formatters
src/Command/CommandHandler.php Command routing for webhook updates
public/webhook.php            Production webhook endpoint
tests/                        Unit / Integration / Benchmark / EndToEnd
```

See [.claude/docs/architecture.md](.claude/docs/architecture.md) for the full tree and layer diagram.

## Golden Rules

- Use `TelegramBot` service accessors — never instantiate `Api/Methods/*` classes directly, and don't call `$bot->api()->call()` when a service method already exists.
- `MessageService` and `MediaService` auto-escape `text`/`caption` when `parse_mode => 'MarkdownV2'`. Use the `*Raw()` variants only when the string is already MarkdownV2-formatted.
- `declare(strict_types=1);` at the top of every file; one class per file; namespace mirrors directory structure under `AhmCho\Telegram`.
- Changing public behavior means updating both this doc set (if internals moved) and the matching `docs/NN-*.md` page (if user-facing behavior changed).

---

**Last Updated:** 2026-07-02
**Framework Version:** 1.1
**PHP Version:** 8.1+
