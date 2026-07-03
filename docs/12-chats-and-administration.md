[← Documentation Home](README.md)

# Chats & Administration

`ChatService` (`src/Api/Methods/ChatService.php`), reached via
`$bot->chats()`, covers everything related to a chat as an entity: reading
its info, managing membership and permissions, pinning messages, the menu
button, and answering callback queries.

## Reading chat information

```php
$chat = $bot->chats()->getChat(['chat_id' => $chatId]);
// $chat['type'] is one of 'private', 'group', 'supergroup', 'channel'

$member = $bot->chats()->getMember(['chat_id' => $chatId, 'user_id' => $userId]);
$admins = $bot->chats()->getAdministrators(['chat_id' => $chatId]);
$count = $bot->chats()->getMemberCount(['chat_id' => $chatId]); // int
```

## Chat actions ("typing...")

```php
use AhmCho\Telegram\Enums\ChatAction;

$bot->chats()->sendAction([
    'chat_id' => $chatId,
    'action' => ChatAction::TYPING->value, // shows "typing..." for a few seconds
]);
```

`ChatAction` (`src/Enums/ChatAction.php`) enumerates every action Telegram
supports: `TYPING`, `UPLOAD_PHOTO`, `RECORD_VIDEO`, `UPLOAD_VIDEO`,
`RECORD_VOICE`, `UPLOAD_VOICE`, `UPLOAD_DOCUMENT`, `FIND_LOCATION`,
`RECORD_VIDEO_NOTE`, `UPLOAD_VIDEO_NOTE`. Send the matching action right
before you start a slow operation (like generating a document) so the user
sees appropriate feedback instead of silence.

## Member management

```php
$bot->chats()->banMember(['chat_id' => $chatId, 'user_id' => $userId]);
$bot->chats()->unbanMember(['chat_id' => $chatId, 'user_id' => $userId]);

$bot->chats()->restrictMember([
    'chat_id' => $chatId,
    'user_id' => $userId,
    'permissions' => ['can_send_messages' => false],
]);

$bot->chats()->promoteMember([
    'chat_id' => $chatId,
    'user_id' => $userId,
    'can_manage_chat' => true,
    'can_delete_messages' => true,
]);

$bot->chats()->leave(['chat_id' => $chatId]); // the bot leaves the chat
```

## Pinning messages

```php
$bot->chats()->pinMessage(['chat_id' => $chatId, 'message_id' => $messageId]);
$bot->chats()->unpinMessage(['chat_id' => $chatId, 'message_id' => $messageId]);
$bot->chats()->unpinAllMessages(['chat_id' => $chatId]);
```

## Chat settings

```php
$bot->chats()->setChatTitle(['chat_id' => $chatId, 'title' => 'New Title']);
$bot->chats()->setChatDescription(['chat_id' => $chatId, 'description' => 'About this chat']);
$bot->chats()->setChatPhoto(['chat_id' => $chatId, 'photo' => new CURLFile('/path/to/photo.jpg')]);
$bot->chats()->deleteChatPhoto(['chat_id' => $chatId]);

$bot->chats()->setChatPermissions([
    'chat_id' => $chatId,
    'permissions' => [
        'can_send_messages' => true,
        'can_send_photos' => false,
    ],
]);
```

All of these require the bot to be an administrator with the relevant
right in the target chat — Telegram returns an `ApiException` (see
[Error Handling](18-error-handling.md)) if it isn't.

## The chat menu button

The menu button is the button next to a private chat's text input that
can open a Mini App or a command list:

```php
$button = $bot->chats()->getMenuButton(['chat_id' => $chatId]); // omit chat_id for the global default

$bot->chats()->setMenuButton([
    'chat_id' => $chatId,
    'menu_button' => ['type' => 'commands'], // or ['type' => 'web_app', 'text' => '...', 'web_app' => ['url' => '...']]
]);
```

## Answering callback queries

Every tap on an inline keyboard button (see [Keyboards](08-keyboards.md))
generates a `callback_query` update that **must** be acknowledged, or the
user's Telegram client shows a loading spinner on the button until the
query times out:

```php
$bot->chats()->answerCallbackQuery([
    'callback_query_id' => $update['callback_query']['id'],
]);

// Or show a toast / alert to the user instead of just clearing the spinner:
$bot->chats()->answerCallbackQuery([
    'callback_query_id' => $update['callback_query']['id'],
    'text' => 'Saved!',
    'show_alert' => false, // true shows a blocking alert dialog instead of a toast
]);
```

Call this exactly once per callback query, as early in your handling as
practical — you can still do more work (like sending a follow-up message)
afterward.

## Method reference

| Method | Purpose |
|---|---|
| `sendAction`, `getChat`, `getMember`, `getAdministrators`, `getMemberCount` | Read chat info / send typing-style actions |
| `banMember`, `unbanMember`, `restrictMember`, `promoteMember`, `leave` | Membership management |
| `pinMessage`, `unpinMessage`, `unpinAllMessages` | Pinned message management |
| `setChatTitle`, `setChatDescription`, `setChatPhoto`, `deleteChatPhoto`, `setChatPermissions` | Chat settings |
| `getMenuButton`, `setMenuButton` | The chat menu button |
| `answerCallbackQuery` | Acknowledge an inline keyboard tap |

---

[← Previous: Webhooks](11-webhooks.md) | [Documentation Home](README.md) | [Next: Polls, Inline Mode & Topics →](13-polls-inline-topics.md)
