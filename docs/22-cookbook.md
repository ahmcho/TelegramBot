[← Documentation Home](README.md)

# Cookbook

End-to-end recipes that combine multiple pieces of the framework. Each one
assumes you've read the pages it links to — this page is about
composition, not re-explaining the basics.

## Recipe: A complete command-driven webhook bot

Combines [Commands](10-commands.md), [Webhooks](11-webhooks.md), and
[Keyboards](08-keyboards.md).

```php
<?php
// public/webhook.php
require_once __DIR__ . '/../autoload.php';

use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;

$bot = new TelegramBot();

$bot->commands()
    ->register('start', function ($bot, $chatId, $args) {
        $keyboard = InlineKeyboardBuilder::create()
            ->addRow(Button::callback('ℹ️ Help', 'help'), Button::callback('📊 Stats', 'stats'))
            ->build();

        $bot->messages()->send([
            'chat_id' => $chatId,
            'text' => "👋 Welcome! Choose an option:",
            'reply_markup' => $keyboard,
        ]);
    }, 'Start the bot')
    ->register('help', function ($bot, $chatId, $args) {
        $bot->commands()->sendHelp($chatId);
    }, 'Show this help message')
    ->setDefault(function ($bot, $chatId, $command, $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => "Unknown command: /{$command}"]);
    });

$bot->processWebhook(function (array $update) use ($bot) {
    if ($bot->commands()->handleUpdate($update)) {
        return;
    }

    // Handle button taps from the keyboard above
    if (isset($update['callback_query'])) {
        $query = $update['callback_query'];
        $chatId = $query['message']['chat']['id'];

        $bot->chats()->answerCallbackQuery(['callback_query_id' => $query['id']]);

        match ($query['data']) {
            'help' => $bot->commands()->sendHelp($chatId),
            'stats' => $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Stats coming soon.']),
            default => null,
        };
    }
});
```

## Recipe: Broadcasting to every user with progress reporting

Combines [Bulk Operations & Broadcasting](16-bulk-operations.md) and
[Error Handling](18-error-handling.md).

```php
use AhmCho\Telegram\Bulk\BulkSendException;

function broadcastToAllUsers(TelegramBot $bot, array $userIds, string $message): void
{
    try {
        $result = $bot->messages()->broadcast(
            $userIds,
            $message,
            commonParams: ['parse_mode' => 'MarkdownV2'],
            options: ['max_concurrent' => 50, 'delay_ms' => 10]
        );
    } catch (BulkSendException $e) {
        // Every single recipient failed — something is fundamentally broken
        error_log("Broadcast totally failed: {$e->getMessage()}");
        return;
    }

    echo "Delivered to {$result->successful}/{$result->total} ({$result->getSuccessRate()}%)\n";

    foreach ($result->getFailedResults() as $failure) {
        // Individual failures (blocked bot, deactivated account, etc.) — expected at scale
        $reason = $failure['error'];
        if (str_contains($reason, 'bot was blocked')) {
            markUserAsBlocked($failure['chat_id']);
        }
    }
}
```

## Recipe: Uploading a local photo album

Combines [Media & Files](09-media-and-files.md).

```php
$bot->media()->sendMediaGroup([
    'chat_id' => $chatId,
    'media' => [
        [
            'type' => 'photo',
            'media' => new CURLFile('/var/uploads/product-front.jpg'),
            'caption' => 'New arrival — front view',
        ],
        ['type' => 'photo', 'media' => new CURLFile('/var/uploads/product-side.jpg')],
        ['type' => 'photo', 'media' => new CURLFile('/var/uploads/product-back.jpg')],
    ],
]);
```

The framework handles extracting each `CURLFile` into the `attach://`
format Telegram requires for local uploads inside a media group — you
never construct that reference yourself (see
[Media & Files](09-media-and-files.md) and
[HTTP Clients](20-http-clients.md) for exactly how).

## Recipe: Downloading a photo a user sent you

```php
$bot->processWebhook(function (array $update) use ($bot) {
    $photos = $update['message']['photo'] ?? null;
    if ($photos === null) {
        return;
    }

    // Telegram sends multiple resolutions; the last one is the largest
    $largestPhoto = end($photos);

    $file = $bot->media()->getFile(['file_id' => $largestPhoto['file_id']]);
    $url = $bot->media()->getFileDownloadUrl($file['file_path']);

    $localPath = '/var/incoming/' . basename($file['file_path']);
    file_put_contents($localPath, file_get_contents($url));

    $chatId = $update['message']['chat']['id'];
    $bot->messages()->send(['chat_id' => $chatId, 'text' => '📷 Got your photo, thanks!']);
});
```

## Recipe: A forum-based support bot (one topic per ticket)

Combines [Polls, Inline Mode & Topics](13-polls-inline-topics.md) and
[Chats & Administration](12-chats-and-administration.md).

```php
function openSupportTicket(TelegramBot $bot, int $supportGroupId, int $requesterId, string $issue): int
{
    $topic = $bot->topics()->create([
        'chat_id' => $supportGroupId,
        'name' => "Ticket #{$requesterId}",
    ]);

    $threadId = $topic['message_thread_id'];

    $bot->messages()->send([
        'chat_id' => $supportGroupId,
        'message_thread_id' => $threadId,
        'text' => "New ticket from user {$requesterId}:\n{$issue}",
    ]);

    return $threadId;
}

function closeSupportTicket(TelegramBot $bot, int $supportGroupId, int $threadId): void
{
    $bot->topics()->close(['chat_id' => $supportGroupId, 'message_thread_id' => $threadId]);
}
```

## Recipe: Retrying a critical send without giving up silently

Combines [Retry & Resilience](17-retry-and-resilience.md) and
[Error Handling](18-error-handling.md).

```php
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Exception\HttpClientException;

function sendCriticalAlert(TelegramBot $bot, int $adminChatId, string $message): void
{
    try {
        $bot->sendMessageWithRetry(
            ['chat_id' => $adminChatId, 'text' => $message],
            ['max_retries' => 5, 'initial_delay_ms' => 500]
        );
    } catch (ApiException|HttpClientException $e) {
        // Retries exhausted — this is genuinely down, escalate outside Telegram entirely
        error_log("CRITICAL: could not deliver alert after retries: {$e->getMessage()}");
        mail('oncall@example.com', 'Bot alert delivery failed', $message);
    }
}
```

## Recipe: A minimal inline-mode search bot

Combines [Polls, Inline Mode & Topics](13-polls-inline-topics.md).

```php
$bot->processWebhook(function (array $update) use ($bot) {
    if (!isset($update['inline_query'])) {
        return;
    }

    $query = trim($update['inline_query']['query']);

    $results = $query === ''
        ? []
        : array_map(
            fn($item, $i) => $bot->inline()->createArticle(
                id: (string) $i,
                title: $item['title'],
                message_text: $item['text']
            ),
            searchDatabase($query),
            array_keys(searchDatabase($query))
        );

    $bot->inline()->answer([
        'inline_query_id' => $update['inline_query']['id'],
        'results' => $results,
        'cache_time' => 60,
    ]);
});
```

---

[← Previous: Testing](21-testing.md) | [Documentation Home](README.md)

**You've reached the end of the documentation.** If something wasn't
covered here, the source itself is thoroughly typed and documented — start
from `src/Bot/TelegramBot.php` and follow the service accessors into
`src/Api/Methods/`.
