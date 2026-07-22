# Webhooks, Bulk Operations & Testing

[← Back to CLAUDE.md](../../CLAUDE.md)

## Webhooks

### Setup

```php
$bot = new TelegramBot();

$bot->webhooks()->set([
    'url' => 'https://your-domain.com/public/webhook.php',
    'secret_token' => 'your_secret'
]);
```

### Handling Updates

```php
$update = $bot->getWebhookUpdates();

if ($update && isset($update['message'])) {
    $chatId = $update['message']['chat']['id'];
    $text = $update['message']['text'] ?? '';
    $bot->messages()->send(['chat_id' => $chatId, 'text' => "You said: $text"]);
}
```

### CommandHandler

```php
$bot->commands()
    ->register('start', function(TelegramBot $bot, int $chatId, array $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome!']);
    }, 'Start the bot')
    ->register('help', function(TelegramBot $bot, int $chatId, array $args) {
        $bot->commands()->sendHelp($chatId);
    }, 'Show help');

$bot->processWebhook(function(array $update) use ($bot) {
    $bot->commands()->handleUpdate($update);
});
```

Full `CommandHandler` API is in [api-reference.md](api-reference.md#commandhandler-srccommandcommandhandlerphp).

## Bulk Operations

Config options, `BulkResult` shape, and `BulkSendException` are in [api-reference.md](api-reference.md#bulk-operations-srcbulk).

### sendBulk

```php
$messages = [
    ['chat_id' => 123, 'text' => 'Hello 1'],
    ['chat_id' => 456, 'text' => 'Hello 2'],
];

$result = $bot->messages()->sendBulk($messages);
echo $result->getSuccessRate() . '%';
```

### broadcast

```php
$chatIds = [123, 456, 789];
$result = $bot->messages()->broadcast($chatIds, 'Announcement!');
```

## Testing

```bash
vendor/bin/phpunit                    # all tests
vendor/bin/phpunit tests/Unit/Api/    # specific suite
```

```
tests/
├── Unit/           # Unit tests (mocked HTTP)
├── Integration/    # Integration tests
├── Benchmark/      # Bulk operation benchmarks
├── EndToEnd/       # E2E tests
├── Helpers/        # MockHttpClient, MockTelegramResponse, TestDataFactory, WebhookStreamWrapper
└── bootstrap.php
```

`MockHttpClient` implements `HttpClientInterface` and records all requests for assertion.
