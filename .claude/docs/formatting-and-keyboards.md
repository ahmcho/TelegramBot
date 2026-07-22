# Formatting & Keyboards

[← Back to CLAUDE.md](../../CLAUDE.md)

## Formatters

Both formatters implement `TextFormatterInterface`:

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

**MarkdownV2Formatter** — escapes all MarkdownV2 special chars. Auto-escape is applied by `MessageService` and `MediaService` when `parse_mode = 'MarkdownV2'` (see [api-reference.md](api-reference.md)). For manual formatting:

```php
$f = $bot->formatter(); // MarkdownV2Formatter
$f->bold('text');         // *text*
$f->italic('text');       // _text_
$f->underline('text');    // __text__
$f->strikethrough('text');// ~text~
$f->code('text');         // `text`
$f->pre('text');          // ```\ntext\n```
$f->link('text', $url);
$f->mention('name', $userId);
$f->hashtag('tag');
```

**HtmlFormatter** — wraps in HTML tags, escapes via `htmlspecialchars`. Does not perform auto-escaping in service methods:

```php
$f = new HtmlFormatter();
$f->bold('text');      // <b>text</b>
$f->italic('text');    // <i>text</i>
$f->underline('text'); // <u>text</u>
// ... same interface, HTML output
```

## Keyboard Builders

### InlineKeyboardBuilder

```php
$keyboard = InlineKeyboardBuilder::create()
    ->addRow(
        Button::callback('Button 1', 'data_1'),
        Button::url('Google', 'https://google.com')
    )
    ->addRow(
        Button::callback('Button 2', 'data_2')
    )
    ->build(); // returns JSON string

$bot->messages()->send([
    'chat_id' => $chatId,
    'text' => 'Pick one',
    'reply_markup' => $keyboard,
]);
```

**Button types:** `Button::callback(text, data)`, `Button::url(text, url)`, `Button::switchInline(text, query)`, `Button::switchInlineCurrent(text, query)`, `Button::text(text)`

### ReplyKeyboardBuilder

```php
$options = new ReplyKeyboardOptions(
    resizeKeyboard: true,
    oneTimeKeyboard: true,
    selective: false,
    isPersistent: false
);

$keyboard = ReplyKeyboardBuilder::create($options)
    ->addRow(Button::text('Option 1'), Button::text('Option 2'))
    ->addRow(Button::text('Option 3'))
    ->build();
```

`ReplyKeyboardBuilder::addRow()` accepts one or more `Button` objects; only `text` is used (callback data is ignored for reply keyboards).
