[← Documentation Home](README.md)

# Media & Files

`MediaService` (`src/Api/Methods/MediaService.php`), reached via
`$bot->media()`, handles every kind of media message: photos, documents,
video, audio, voice notes, stickers, locations, contacts, polls (as media
attachments), dice, media groups (albums), and downloading files the bot
receives.

## The three ways to reference media

Every media-sending method accepts the media itself in one of three forms:

1. **A `file_id` string** — the fastest option, reusing a file Telegram
   already has cached from a previous upload.
2. **A URL string** — Telegram fetches the file itself.
3. **A `CURLFile`** — uploads a file from your local filesystem.

```php
// file_id (fastest — no upload happens)
$bot->media()->sendPhoto(['chat_id' => $chatId, 'photo' => 'AgACAgIAAxk...']);

// URL (Telegram fetches it)
$bot->media()->sendPhoto(['chat_id' => $chatId, 'photo' => 'https://example.com/cat.jpg']);

// Local file upload
$bot->media()->sendPhoto([
    'chat_id' => $chatId,
    'photo' => new CURLFile('/path/to/local/cat.jpg'),
]);
```

## Sending each media type

```php
$bot->media()->sendPhoto(['chat_id' => $chatId, 'photo' => $photo, 'caption' => 'A cat']);
$bot->media()->sendDocument(['chat_id' => $chatId, 'document' => $file]);
$bot->media()->sendVideo(['chat_id' => $chatId, 'video' => $video]);
$bot->media()->sendAudio(['chat_id' => $chatId, 'audio' => $audio, 'title' => 'Song', 'performer' => 'Artist']);
$bot->media()->sendVoice(['chat_id' => $chatId, 'voice' => $voiceNote]);
$bot->media()->sendAnimation(['chat_id' => $chatId, 'animation' => $gif]);
$bot->media()->sendSticker(['chat_id' => $chatId, 'sticker' => $stickerFileId]);
$bot->media()->sendLocation(['chat_id' => $chatId, 'latitude' => 51.5, 'longitude' => -0.12]);
$bot->media()->sendVenue(['chat_id' => $chatId, 'latitude' => 51.5, 'longitude' => -0.12, 'title' => 'Big Ben', 'address' => 'London']);
$bot->media()->sendContact(['chat_id' => $chatId, 'phone_number' => '+1234567890', 'first_name' => 'Alice']);
$bot->media()->sendDice(['chat_id' => $chatId, 'emoji' => '🎲']);
```

### Caption auto-escaping

Every method that accepts a `caption` (`sendPhoto`, `sendDocument`,
`sendVideo`, `sendAudio`, `sendVoice`, `sendAnimation`) auto-escapes it the
same way `MessageService::send()` auto-escapes `text` — only when
`parse_mode => 'MarkdownV2'` is set. See
[Formatting Text](07-formatting-text.md) for the full explanation of what
auto-escaping does and does not cover. There are no `Raw()` caption
variants on `MediaService` — if you need to skip escaping, format the
caption manually and omit `parse_mode`, or pass already-escaped text.

## Sending an album (media group)

`sendMediaGroup()` sends 2–10 photos/videos/documents/audios as a single
album:

```php
$bot->media()->sendMediaGroup([
    'chat_id' => $chatId,
    'media' => [
        ['type' => 'photo',    'media' => 'file_id_or_url', 'caption' => 'First item'],
        ['type' => 'video',    'media' => 'file_id_or_url', 'width' => 1280, 'height' => 720],
        ['type' => 'audio',    'media' => 'file_id_or_url', 'title' => 'Song', 'performer' => 'Artist'],
        ['type' => 'document', 'media' => 'file_id_or_url'],
    ],
]);
// Returns an array of Message objects, one per media item.
```

Each element is an `InputMedia*` array with at minimum `type` and `media`.
Only the **first** item's `caption`/`parse_mode` is shown as the album's
caption in the chat — Telegram ignores captions on the rest.

### Uploading local files in a media group

Pass a `CURLFile` as an item's `media` (or `thumbnail`) value:

```php
$bot->media()->sendMediaGroup([
    'chat_id' => $chatId,
    'media' => [
        ['type' => 'photo', 'media' => new CURLFile('/path/to/photo1.jpg'), 'caption' => 'Local photo'],
        ['type' => 'photo', 'media' => new CURLFile('/path/to/photo2.jpg')],
    ],
]);
```

Telegram requires local files inside a media group to be referenced by an
`attach://<name>` string within the JSON-encoded `media` array, with the
actual file bytes sent as a separate multipart field named `<name>` — not
embedded directly. You never have to build that yourself:
`MediaService::sendMediaGroup()` extracts each `CURLFile` into a
`media_attach_N` field and rewrites the reference to `attach://media_attach_N`
before the request is sent (`src/Api/Methods/MediaService.php`). This
works identically whether the framework picked `CurlHttpClient` or
`StreamHttpClient` for you — see [HTTP Clients](20-http-clients.md) for how
the actual multipart body gets built.

## Downloading a file the bot received

When a user sends your bot a photo, document, or voice note, the update
contains a `file_id`, not the file's bytes. Resolving it to an actual
download takes two steps:

```php
// 1. Resolve the file_id to a file_path
$file = $bot->media()->getFile(['file_id' => $incomingFileId]);
// $file = ['file_id' => ..., 'file_unique_id' => ..., 'file_size' => ..., 'file_path' => 'photos/file_123.jpg']

// 2. Build the full download URL
$url = $bot->media()->getFileDownloadUrl($file['file_path']);
// => "https://api.telegram.org/file/bot<TOKEN>/photos/file_123.jpg"

// Download it however you like
file_put_contents('/local/path/file.jpg', file_get_contents($url));
```

`getFileDownloadUrl()` builds the URL using the same API base URL and
token from your `BotConfig` — if you've pointed the bot at a self-hosted
Bot API server via a custom `apiUrl`, the download URL is built from that
same base, not hardcoded to `api.telegram.org`
(`src/Api/Methods/MediaService.php:185-192`).

**Telegram file size limits:** bots can download files up to 20 MB via
this method. Larger files require a self-hosted Bot API server (outside
the scope of this framework's defaults).

## Custom emoji stickers

```php
$bot->media()->getCustomEmojiStickers(['custom_emoji_ids' => ['5397274858228847992']]);
```

## Method reference

| Method | Returns |
|---|---|
| `sendPhoto`, `sendDocument`, `sendVideo`, `sendAudio`, `sendVoice`, `sendAnimation` | `array` (Message), auto-escapes caption |
| `sendSticker`, `sendLocation`, `sendVenue`, `sendContact`, `sendPoll`, `sendDice` | `array` (Message) |
| `getCustomEmojiStickers(array $params): array` | Array of Sticker objects |
| `sendMediaGroup(array $params): array` | Array of Message objects |
| `getFile(array $params): array` | File object (includes `file_path`) |
| `getFileDownloadUrl(string $filePath): string` | Full HTTPS download URL |

---

[← Previous: Keyboards](08-keyboards.md) | [Documentation Home](README.md) | [Next: Commands →](10-commands.md)
