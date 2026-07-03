[← Documentation Home](README.md)

# Formatting Text

Telegram supports three ways to style message text: legacy `Markdown`,
`MarkdownV2`, and `HTML`, selected via the `parse_mode` parameter. This
framework gives you a `TextFormatterInterface` to build formatted text
without memorizing escaping rules by hand.

## The two formatters

```php
use AhmCho\Telegram\Formatting\MarkdownV2Formatter;
use AhmCho\Telegram\Formatting\HtmlFormatter;
```

Both implement the same interface (`src/Formatting/TextFormatterInterface.php`):

```php
interface TextFormatterInterface
{
    public function escape(string $text): string;
    public function bold(string $text): string;
    public function italic(string $text): string;
    public function underline(string $text): string;
    public function strikethrough(string $text): string;
    public function code(string $text): string;
    public function pre(string $text): string;
    public function link(string $text, string $url): string;
    public function mention(string $text, string $username): string;
    public function hashtag(string $tag): string;
}
```

`$bot->formatter()` gives you a `MarkdownV2Formatter` by default (this is
the only formatter `TelegramBot` wires up automatically — construct
`HtmlFormatter` yourself if you want HTML instead):

```php
$f = $bot->formatter();

$f->bold('text');           // *text*
$f->italic('text');         // _text_
$f->underline('text');      // __text__
$f->strikethrough('text');  // ~text~
$f->code('text');           // `text`
$f->pre('text');            // ```\ntext\n```
$f->link('Google', 'https://google.com');
$f->mention('Ahmad', '123456789'); // links to tg://user?id=123456789
$f->hashtag('news');         // #news
```

```php
use AhmCho\Telegram\Formatting\HtmlFormatter;

$f = new HtmlFormatter();
$f->bold('text');    // <b>text</b>
$f->italic('text');  // <i>text</i>
$f->underline('text'); // <u>text</u>
$f->strikethrough('text'); // <s>text</s>
$f->code('text');    // <code>text</code>
$f->pre('text');     // <pre>text</pre>
```

Every builder method escapes its own text argument for you (`bold()`,
`italic()`, etc. call `escape()` internally) — you only need to call
`escape()` yourself for plain, unformatted text you're interpolating.

## `MarkdownV2Formatter::escape()` in detail

MarkdownV2 requires escaping any of these 18 characters when they appear
as literal text rather than formatting syntax
(`src/Formatting/MarkdownV2Formatter.php:14-18`):

```
_ * [ ] ( ) ~ ` > # + - = | { } . !
```

```php
$f = new MarkdownV2Formatter();
$f->escape('Price: $5.99 (limited time!)');
// => "Price: $5\.99 \(limited time\!\)"
```

Backslash itself is escaped first, before the loop over special
characters, specifically to avoid double-escaping
(`MarkdownV2Formatter.php:22-27`) — you don't need to think about this
detail, just know that `escape()` handles it correctly for arbitrary
input, including text that already contains backslashes.

`pre()` is special-cased: **only** backtick and backslash are escaped
inside a pre-formatted block, since every other character is treated as
literal monospace text by Telegram, not Markdown syntax
(`MarkdownV2Formatter.php:57-67`).

## `HtmlFormatter::escape()`

Uses PHP's `htmlspecialchars()` with `ENT_QUOTES | ENT_HTML5` — the same
approach any web developer would use to prevent HTML injection, applied
here to prevent Telegram's HTML parser from misinterpreting user text as
markup.

## Auto-escaping in services vs. manual formatting

There are two ways to get correctly-escaped MarkdownV2 text into a
message, and it's important to know which one you're using:

1. **Auto-escaping** (covered in [Sending Messages](06-sending-messages.md)):
   set `parse_mode => 'MarkdownV2'` and call `send()` — the entire `text`
   field is escaped for you, meaning **none of it will be interpreted as
   Markdown syntax**. This is correct for plain user text but wrong if you
   wanted some of it bold.

   ```php
   $bot->messages()->send([
       'chat_id' => $chatId,
       'text' => "Hello *world*", // the * characters get escaped too — sent literally as "Hello \*world\*"
       'parse_mode' => 'MarkdownV2',
   ]);
   ```

2. **Manual formatting + `Raw()`**: build the text yourself with the
   formatter (escaping only the *data*, not your own formatting markers),
   then use the `Raw` variant so the framework doesn't escape it a second
   time:

   ```php
   $f = $bot->formatter();
   $text = $f->bold('Hello') . ' ' . $f->escape($userSuppliedWord);

   $bot->messages()->sendRaw([
       'chat_id' => $chatId,
       'text' => $text,
       'parse_mode' => 'MarkdownV2',
   ]);
   // Correctly bold "Hello", with $userSuppliedWord escaped but not bolded.
   ```

Mixing these up is the single most common formatting bug: calling `send()`
(auto-escaping) with text that already contains formatting markers you
built yourself corrupts the markers, and calling `sendRaw()` with
unescaped raw user text risks a 400 error from Telegram (or, worse, lets a
user's message accidentally trigger Markdown formatting they didn't
intend).

## The `ParseMode` enum

`src/Enums/ParseMode.php` defines the three modes as a backed enum:

```php
enum ParseMode: string
{
    case MARKDOWN = 'Markdown';
    case MARKDOWN_V2 = 'MarkdownV2';
    case HTML = 'HTML';
}
```

Internally, the framework compares against `ParseMode::MARKDOWN_V2->value`
rather than the raw string `'MarkdownV2'` wherever auto-escaping decisions
are made, so if you're writing code that needs to check this yourself
(e.g. custom middleware), prefer the enum over a string literal:

```php
use AhmCho\Telegram\Enums\ParseMode;

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => $text,
    'parse_mode' => ParseMode::MARKDOWN_V2->value,
]);
```

**A note on legacy `Markdown`:** it uses different (and more limited)
escaping rules than `MarkdownV2`, and this framework's auto-escaping only
ever targets `MarkdownV2` — auto-escaping does **not** apply to
`parse_mode => 'Markdown'`. If you use the legacy mode, you're responsible
for escaping it yourself (Telegram recommends migrating to `MarkdownV2`
for exactly this reason — it's the actively maintained format).

---

[← Previous: Sending Messages](06-sending-messages.md) | [Documentation Home](README.md) | [Next: Keyboards →](08-keyboards.md)
