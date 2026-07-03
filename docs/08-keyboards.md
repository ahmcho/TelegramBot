[← Documentation Home](README.md)

# Keyboards

Telegram supports two kinds of custom keyboards attached to a message:
**inline keyboards** (buttons attached to the message itself, which send a
callback to your bot when tapped) and **reply keyboards** (buttons that
replace the user's regular keyboard, which send a normal text message when
tapped). This framework provides a fluent builder for each.

## Inline keyboards

```php
use AhmCho\Telegram\Keyboard\InlineKeyboardBuilder;
use AhmCho\Telegram\Keyboard\Button;

$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::callback('Button 1', 'data_1'),
        Button::url('Google', 'https://google.com')
    )
    ->addRow(
        Button::callback('Button 2', 'data_2')
    )
    ->build(); // returns a JSON string, ready for reply_markup

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Pick one',
    'reply_markup' => $keyboard,
]);
```

Each call to `addRow(...$buttons)` adds one row; call it multiple times
for multiple rows. `build()` returns the JSON string Telegram expects for
`reply_markup`; `toArray()` returns the underlying PHP array if you need
to inspect or merge it before encoding yourself.

### Button types

```php
Button::callback(string $text, string $data);        // sends a callback_query with this data
Button::url(string $text, string $url);               // opens a URL
Button::switchInline(string $text, string $query = ''); // opens inline mode in another chat
Button::switchInlineCurrent(string $text, string $query = ''); // opens inline mode in the current chat
Button::text(string $text);                            // plain text (used by reply keyboards, see below)
```

### Handling a button tap

A tapped inline button sends your bot a `callback_query` update, not a
regular message. Read [Chats & Administration](12-chats-and-administration.md)
for `answerCallbackQuery()` — Telegram requires you to acknowledge every
callback query or the user's client shows a loading spinner until it times
out:

```php
$bot->processWebhook(function (array $update) use ($bot) {
    if (!isset($update['callback_query'])) {
        return;
    }

    $query = $update['callback_query'];
    $chatId = $query['message']['chat']['id'];
    $data = $query['data']; // e.g. "data_1"

    // Always acknowledge, even if you don't need to show a message
    $bot->chats()->answerCallbackQuery(['callback_query_id' => $query['id']]);

    $bot->messages()->send([
        'chat_id' => $chatId,
        'text' => "You picked: {$data}",
    ]);
});
```

## Reply keyboards

```php
use AhmCho\Telegram\Keyboard\ReplyKeyboardBuilder;
use AhmCho\Telegram\Keyboard\ReplyKeyboardOptions;
use AhmCho\Telegram\Keyboard\Button;

$options = new ReplyKeyboardOptions(
    resizeKeyboard: true,   // shrink the keyboard to fit the buttons
    oneTimeKeyboard: true,  // hide it again after one use
    selective: false,       // show only to specific users (advanced; rarely needed)
    isPersistent: false     // keep visible even after oneTimeKeyboard hides it
);

$keyboard = ReplyKeyboardBuilder::create($options)
    ->addRow(Button::text('Option 1'), Button::text('Option 2'))
    ->addRow(Button::text('Option 3'))
    ->build();

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Choose an option:',
    'reply_markup' => $keyboard,
]);
```

`ReplyKeyboardBuilder::addRow()` accepts one or more `Button` objects, but
**only the `text` field is used** — `Button::callback()`'s `data` and
`Button::url()`'s `url` are silently ignored if you pass those button
types here, since reply keyboards have no concept of callback data or
inline URLs. Use `Button::text()` for reply keyboards.

A tapped reply-keyboard button sends a completely ordinary text message
from the user, with `text` equal to the button's label — you handle it
exactly like any other message:

```php
if (($update['message']['text'] ?? null) === 'Option 1') {
    // handle it
}
```

### `ReplyKeyboardOptions` defaults

All four options default to `false` (`src/Keyboard/ReplyKeyboardOptions.php:14-19`)
— pass an explicit `ReplyKeyboardOptions` only for the ones you want to
turn on.

## Removing a keyboard

Telegram's API has a dedicated `remove_keyboard` reply markup for clearing
a previously-shown reply keyboard. This framework doesn't wrap it in a
builder (it's a single flag, not worth a fluent API) — send it directly:

```php
$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Keyboard removed.',
    'reply_markup' => json_encode(['remove_keyboard' => true]),
]);
```

## Under the hood

Both builders implement `KeyboardBuilderInterface` and produce a JSON
string via `build()`:

- `InlineKeyboardBuilder::toArray()` returns
  `['inline_keyboard' => [[...button arrays...], ...]]`, where each button
  is `Button::toArray()` — a full associative array with `text` plus
  whichever of `url`, `callback_data`, `switch_inline_query`, or
  `switch_inline_query_current_chat` applies
  (`src/Keyboard/Button.php:90-111`).
- `ReplyKeyboardBuilder::toArray()` returns
  `['keyboard' => [[{'text': ...}, ...], ...], 'resize_keyboard' => ...,
  'one_time_keyboard' => ..., 'selective' => ..., 'is_persistent' => ...]`
  — each button reduced to a `{"text": "..."}` object, matching the
  `KeyboardButton` shape Telegram's API spec defines.

You rarely need `toArray()` directly — `build()`'s JSON string is what
`reply_markup` expects — but it's there if you want to inspect or compose
keyboards programmatically before encoding.

---

[← Previous: Formatting Text](07-formatting-text.md) | [Documentation Home](README.md) | [Next: Media & Files →](09-media-and-files.md)
