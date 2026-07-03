[← Documentation Home](README.md)

# Sending Messages

`MessageService` (`src/Api/Methods/MessageService.php`), reached via
`$bot->messages()`, covers every text-message operation: sending, editing,
deleting, forwarding, copying, and sending in bulk.

## Sending a message

```php
$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Hello, world!',
]);
```

`send()` accepts the same parameters as Telegram's
[`sendMessage`](https://core.telegram.org/bots/api#sendmessage) — anything
you'd find in Telegram's own docs works here: `parse_mode`,
`reply_markup`, `disable_notification`, `message_thread_id`,
`reply_parameters`, and so on.

## Auto-escaping: the most important thing on this page

If you set `'parse_mode' => 'MarkdownV2'`, the `text` field is
**automatically escaped** before the request is sent:

```php
$username = 'user_name.99'; // contains MarkdownV2 special characters: _ and .

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => "Welcome, {$username}!",
    'parse_mode' => 'MarkdownV2',
]);
// Sent to Telegram as: "Welcome, user\_name\.99\!" — every special char escaped.
```

Without this, Telegram would reject the message outright (MarkdownV2 has
18 characters — `_ * [ ] ( ) ~ \` > # + - = | { } . !` — that must be
escaped with a backslash or the API returns a 400 error). This framework
escapes them for you automatically so you can interpolate arbitrary
user-generated text without thinking about it.

**This only applies when `parse_mode` is exactly `MarkdownV2`.** If you
omit `parse_mode`, or use `HTML` or legacy `Markdown`, no auto-escaping
happens — see [Formatting Text](07-formatting-text.md) for how those modes
differ.

### `*Raw()` methods: when you've already formatted the text yourself

If your text is *already* valid MarkdownV2 (for example, you built it
using `$bot->formatter()->bold(...)` — see
[Formatting Text](07-formatting-text.md)), auto-escaping it again would
double-escape your formatting markers and break the output. Use the `Raw`
variant to skip auto-escaping:

```php
$formatter = $bot->formatter();
$text = $formatter->bold('Important:') . ' ' . $formatter->escape($userInput);

$bot->messages()->sendRaw([
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => 'MarkdownV2',
]);
```

Every auto-escaping method has a `Raw` counterpart: `send()`/`sendRaw()`,
`editText()`/`editTextRaw()`, `editCaption()`/`editCaptionRaw()`,
`sendBulk()`/`sendBulkRaw()`, `broadcast()`/`broadcastRaw()`.

## Editing and deleting

```php
$bot->messages()->editText([
    'chat_id' => $chatId,
    'message_id' => $messageId,
    'text' => 'Updated text',
]); // auto-escapes if parse_mode => MarkdownV2, same as send()

$bot->messages()->editCaption([
    'chat_id' => $chatId,
    'message_id' => $messageId,
    'caption' => 'Updated caption',
]);

$bot->messages()->delete([
    'chat_id' => $chatId,
    'message_id' => $messageId,
]);
```

## Forwarding and copying

```php
// Forward keeps the "Forwarded from" attribution
$bot->messages()->forward([
    'chat_id' => $destinationChatId,
    'from_chat_id' => $sourceChatId,
    'message_id' => $messageId,
]);

// Copy sends a fresh, unattributed copy of the message
$bot->messages()->copy([
    'chat_id' => $destinationChatId,
    'from_chat_id' => $sourceChatId,
    'message_id' => $messageId,
]);
```

## Sending to many chats

`sendBulk()` and `broadcast()` send in parallel rather than one at a time.
This is covered in full in
[Bulk Operations & Broadcasting](16-bulk-operations.md) — the short
version:

```php
// Different text per recipient
$result = $bot->messages()->sendBulk([
    ['chat_id' => 111, 'text' => 'Hi Alice'],
    ['chat_id' => 222, 'text' => 'Hi Bob'],
]);

// Same text, many recipients
$result = $bot->messages()->broadcast(
    [111, 222, 333],
    'Scheduled maintenance tonight at 10pm UTC.'
);

echo "{$result->successful}/{$result->total} delivered ({$result->getSuccessRate()}%)";
```

Both auto-escape MarkdownV2 by default (per-message for `sendBulk`, once
for the shared text in `broadcast`); `sendBulkRaw()` / `broadcastRaw()`
skip escaping, same convention as above.

## Method reference

| Method | Telegram API method | Auto-escapes? |
|---|---|---|
| `send(array $params): array` | `sendMessage` | Yes |
| `sendRaw(array $params): array` | `sendMessage` | No |
| `editText(array $params): array` | `editMessageText` | Yes |
| `editTextRaw(array $params): array` | `editMessageText` | No |
| `editCaption(array $params): array` | `editMessageCaption` | Yes |
| `editCaptionRaw(array $params): array` | `editMessageCaption` | No |
| `delete(array $params): mixed` | `deleteMessage` | n/a |
| `forward(array $params): array` | `forwardMessage` | n/a |
| `copy(array $params): array` | `copyMessage` | n/a |
| `sendBulk(array $messages, array $options = []): BulkResult` | `sendMessage` × N | Yes |
| `sendBulkRaw(array $messages, array $options = []): BulkResult` | `sendMessage` × N | No |
| `broadcast(array $chatIds, string $text, array $commonParams = [], array $options = []): BulkResult` | `sendMessage` × N | Yes |
| `broadcastRaw(...)` | `sendMessage` × N | No |

Every method returns whatever Telegram returns, decoded into a plain PHP
array (or throws — see [Error Handling](18-error-handling.md)) — this
framework never invents its own response shape on top of Telegram's.

---

[← Previous: The Bot Facade](05-the-bot-facade.md) | [Documentation Home](README.md) | [Next: Formatting Text →](07-formatting-text.md)
