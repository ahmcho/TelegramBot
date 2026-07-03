[← Documentation Home](README.md)

# Invite Links

`InviteLinksService` (`src/Api/Methods/InviteLinksService.php`), reached
via `$bot->inviteLinks()`, manages custom invite links for chats and
channels the bot administers. Unlike a chat's single permanent invite
link, a bot can create any number of named, expiring, member-limited, or
approval-required links.

## Creating a link

```php
$link = $bot->inviteLinks()->create([
    'chat_id' => $chatId,
    'name' => 'Marketing Campaign A',
    'expire_date' => time() + 86400,  // expires in 24 hours
    'member_limit' => 100,             // max uses
]);

$inviteUrl = $link['invite_link']; // e.g. https://t.me/+AbCdEfGhIjK
```

`creates_join_request` makes the link require manual approval instead of
letting members in immediately — useful for gated communities:

```php
$link = $bot->inviteLinks()->create([
    'chat_id' => $chatId,
    'name' => 'Apply to join',
    'creates_join_request' => true,
]);
```

## Editing and revoking

```php
$bot->inviteLinks()->edit([
    'chat_id' => $chatId,
    'invite_link' => $inviteUrl,
    'name' => 'Renamed Campaign',
    'member_limit' => 50,
]);

$bot->inviteLinks()->revoke([
    'chat_id' => $chatId,
    'invite_link' => $inviteUrl,
]); // permanently disables this specific link; a new one must be created to replace it
```

## The chat's primary invite link

Every chat also has one non-named "primary" invite link, separate from
any custom links you create:

```php
$primaryLink = $bot->inviteLinks()->export(['chat_id' => $chatId]);
// Generates a new primary invite link, invalidating any previous one
```

## Reading link info and statistics

```php
$info = $bot->inviteLinks()->get(['chat_id' => $chatId, 'invite_link' => $inviteUrl]);

$counts = $bot->inviteLinks()->getCounts(['chat_id' => $chatId]);
// Aggregate stats on pending join requests across the chat's invite links

$members = $bot->inviteLinks()->getMembers([
    'chat_id' => $chatId,
    'invite_link' => $inviteUrl,
]);
// Members who joined specifically via this link
```

## Subscription invite links

Telegram supports invite links tied to a paid subscription (Telegram
Stars) for channels:

```php
$bot->inviteLinks()->editSubscription([
    'chat_id' => $chatId,
    'invite_link' => $inviteUrl,
    'name' => 'Premium tier',
]);
```

## Method reference

| Method | Telegram API method |
|---|---|
| `create(array $params): array` | `createChatInviteLink` |
| `edit(array $params): array` | `editChatInviteLink` |
| `revoke(array $params): array` | `revokeChatInviteLink` |
| `export(array $params): mixed` | `exportChatInviteLink` |
| `get(array $params): array` | `getChatInviteLink` |
| `getCounts(array $params): array` | `getChatInviteLinkCounts` |
| `getMembers(array $params): array` | `getChatInviteLinkMembers` |
| `editSubscription(array $params): array` | `editChatSubscriptionInviteLink` |

All methods require `chat_id`; every method other than `create()` and
`export()` also requires `invite_link` to identify which link you're
operating on. The bot must have the `can_invite_users` administrator
right in the target chat.

---

[← Previous: Polls, Inline Mode & Topics](13-polls-inline-topics.md) | [Documentation Home](README.md) | [Next: Games & Payments →](15-games-and-payments.md)
