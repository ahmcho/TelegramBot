[← Documentation Home](README.md)

# Polls, Inline Mode & Topics

This page covers three related but independent services: `PollsService`
(sending and managing polls), `InlineService` (powering inline mode — the
`@yourbot query` search-as-you-type feature), and `TopicsService` (forum
topics in supergroups with Topics enabled).

## Polls

```php
$poll = $bot->polls()->send([
    'chat_id' => $chatId,
    'question' => 'What is your favourite colour?',
    'options' => ['Red', 'Blue', 'Green'],
]);
```

Quiz-mode polls have a single correct answer and can show an explanation
after the user answers:

```php
$bot->polls()->send([
    'chat_id' => $chatId,
    'question' => 'PHP stands for?',
    'options' => ['Hypertext Preprocessor', 'Personal Home Page', 'Pre-Hypertext Processor'],
    'type' => 'quiz',
    'correct_option_id' => 0,
    'explanation' => 'It\'s a recursive acronym.',
]);
```

```php
$bot->polls()->stop(['chat_id' => $chatId, 'message_id' => $pollMessageId]);  // stop accepting answers, returns final poll state
$bot->polls()->close(['chat_id' => $chatId, 'message_id' => $pollMessageId]); // same effect, Telegram's naming for the underlying method
```

`send()` accepts every parameter Telegram's
[`sendPoll`](https://core.telegram.org/bots/api#sendpoll) supports:
`is_anonymous`, `allows_multiple_answers`, `open_period`, `close_date`, and
more.

## Inline mode

Inline mode lets users type `@yourbot search text` in *any* chat and get a
list of results to pick from, without opening a conversation with your
bot. `InlineService` (`src/Api/Methods/InlineService.php`) has two
responsibilities: answering the inline query, and building the individual
result objects.

```php
$bot->processWebhook(function (array $update) use ($bot) {
    if (!isset($update['inline_query'])) {
        return;
    }

    $query = $update['inline_query']['query']; // what the user typed

    $results = [
        $bot->inline()->createArticle(
            id: '1',
            title: 'Result One',
            message_text: "You searched for: {$query}"
        ),
        $bot->inline()->createPhoto(
            id: '2',
            photo_url: 'https://example.com/photo.jpg'
        ),
    ];

    $bot->inline()->answer([
        'inline_query_id' => $update['inline_query']['id'],
        'results' => $results,
        'cache_time' => 300,
    ]);
});
```

### Result builders

Each `create*()` method builds a plain array shaped for the corresponding
`InlineQueryResult*` type — you pass the required fields positionally and
anything extra via the trailing `$options` array, which is merged in:

```php
$bot->inline()->createArticle(string $id, string $title, string $message_text, array $options = []): array;
$bot->inline()->createPhoto(string $id, string $photo_url, array $options = []): array;
$bot->inline()->createVideo(string $id, string $video_url, string $mime_type, string $thumb_url, string $title, array $options = []): array;
$bot->inline()->createAudio(string $id, string $audio_url, string $title, array $options = []): array;
$bot->inline()->createDocument(string $id, string $document_url, string $title, string $mime_type, array $options = []): array;
$bot->inline()->createLocation(string $id, float $latitude, float $longitude, string $title, array $options = []): array;
$bot->inline()->createVenue(string $id, float $latitude, float $longitude, string $title, string $address, array $options = []): array;
$bot->inline()->createContact(string $id, string $phone_number, string $first_name, array $options = []): array;
$bot->inline()->createGame(string $id, string $game_short_name, array $options = []): array;
```

`createGame()` builds only the *inline search result* payload
(`{'type' => 'game', 'id' => ..., 'game_short_name' => ...}`) shown when a
user searches for a game inline — it does **not** send an actual game
message to a chat. For that, see
[Games & Payments](15-games-and-payments.md).

`$options` lets you add anything the builder doesn't take positionally —
a `reply_markup`, a `description`, a `thumbnail_url`, etc.:

```php
$bot->inline()->createArticle('1', 'Title', 'Message text', [
    'description' => 'Shown under the title in the results list',
    'reply_markup' => $keyboard, // a JSON string from InlineKeyboardBuilder, see docs/08-keyboards.md
]);
```

## Forum topics

Forum topics (`TopicsService`, reached via `$bot->topics()`) are threads
within a supergroup that has Topics enabled — think Discord/Slack
channels within one group.

```php
$topic = $bot->topics()->create(['chat_id' => $chatId, 'name' => 'General Discussion']);
$threadId = $topic['message_thread_id'];

// Send a message into that specific topic
$bot->messages()->send([
    'chat_id' => $chatId,
    'message_thread_id' => $threadId,
    'text' => 'Welcome to the topic!',
]);

$bot->topics()->edit(['chat_id' => $chatId, 'message_thread_id' => $threadId, 'name' => 'Renamed']);
$bot->topics()->close(['chat_id' => $chatId, 'message_thread_id' => $threadId]);
$bot->topics()->reopen(['chat_id' => $chatId, 'message_thread_id' => $threadId]);
$bot->topics()->delete(['chat_id' => $chatId, 'message_thread_id' => $threadId]);
$bot->topics()->unpinAll(['chat_id' => $chatId, 'message_thread_id' => $threadId]);

$topics = $bot->topics()->getAll(['chat_id' => $chatId]);
$one = $bot->topics()->get(['chat_id' => $chatId, 'message_thread_id' => $threadId]);
$icons = $bot->topics()->getIconStickers(); // available topic icon stickers, no params
```

### The "General" topic

Every forum-enabled supergroup has one built-in topic that can't be
deleted — the general topic. It has its own dedicated methods rather than
taking a `message_thread_id`:

```php
$bot->topics()->editGeneral(['chat_id' => $chatId, 'name' => 'General']);
$bot->topics()->closeGeneral(['chat_id' => $chatId]);
$bot->topics()->reopenGeneral(['chat_id' => $chatId]);
$bot->topics()->hideGeneral(['chat_id' => $chatId]);
$bot->topics()->unhideGeneral(['chat_id' => $chatId]);
```

---

[← Previous: Chats & Administration](12-chats-and-administration.md) | [Documentation Home](README.md) | [Next: Invite Links →](14-invite-links.md)
