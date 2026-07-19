[← Documentation Home](README.md)

# Testing

This framework is designed to be tested without ever hitting Telegram's
real API. The seam that makes this possible is the same one covered in
[HTTP Clients](20-http-clients.md): `TelegramBot`'s constructor accepts any
`HttpClientInterface`, and the test suite ships a ready-to-use fake:
`MockHttpClient` (`tests/Helpers/MockHttpClient.php`).

## Setting up a test

```php
use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

final class MyBotTest extends TestCase
{
    private MockHttpClient $mockClient;
    private TelegramBot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mockClient = new MockHttpClient();
        $config = new BotConfig(token: 'test_token', loggingEnabled: false);
        $this->bot = new TelegramBot(null, $config, $this->mockClient);
    }
}
```

Always pass a `MockHttpClient` explicitly (never rely on the default
`HttpClientFactory` in tests) — otherwise your test suite would make real
network calls to `api.telegram.org` with a fake token and fail
unpredictably. Setting `loggingEnabled: false` keeps tests from writing to
a real log file on disk (see [Logging](19-logging.md)).

## Scripting responses

```php
public function test_sends_welcome_message(): void
{
    $this->mockClient->setResponse([
        'message_id' => 42,
        'chat' => ['id' => 123],
        'text' => 'Welcome!',
    ]);

    $result = $this->bot->messages()->send(['chat_id' => 123, 'text' => 'Welcome!']);

    $this->assertSame(42, $result['message_id']);
}
```

`MockHttpClient` queues responses in the order you set them — call
`setResponse()` multiple times before making multiple calls in the code
under test, and each call to `request()` consumes the next queued
response. Variants for non-array return types:

```php
$this->mockClient->setBoolResponse(true);   // for methods like deleteMessage, setWebhook
$this->mockClient->setIntResponse(42);      // for methods like getChatMemberCount
$this->mockClient->setStringResponse('x');  // for methods returning a bare string
```

### Simulating a failure

```php
use AhmCho\Telegram\Exception\ApiException;

$this->mockClient->setException(
    new ApiException('Bad Request: chat not found', errorCode: 400, httpCode: 400)
);

$this->expectException(ApiException::class);
$this->bot->messages()->send(['chat_id' => 999999, 'text' => 'x']);
```

Any `\Exception` works here, including `HttpClientException` if you want
to test your retry/error-handling logic against a transport failure
rather than an API-level rejection — see [Error Handling](18-error-handling.md)
for the distinction.

## Asserting on outgoing requests

Beyond checking the return value, you can assert on exactly what was sent
to Telegram:

```php
public function test_broadcast_sends_to_every_chat_id(): void
{
    $this->mockClient->setResponse(['message_id' => 1]);
    $this->mockClient->setResponse(['message_id' => 2]);

    $this->bot->messages()->broadcast([111, 222], 'Announcement');

    $this->assertSame(2, $this->mockClient->getRequestCount());

    $lastRequest = $this->mockClient->getLastRequest();
    $this->assertSame(222, $lastRequest['params']['chat_id']);
    $this->assertSame('Announcement', $lastRequest['params']['text']);

    // Or inspect every request made:
    foreach ($this->mockClient->getRequests() as $request) {
        $this->assertStringContainsString('sendMessage', $request['url']);
    }
}
```

`getLastRequest()` returns `['method' => HttpMethod, 'url' => string,
'params' => array]` or `null` if nothing was sent yet. `clearRequests()`
resets the recorded request log (without touching queued responses) if
you need a clean slate partway through a test.

## Testing webhook handling

`TelegramBot::setInputSource()` lets you feed a fake update body instead
of the real `php://input` (which isn't readable outside an actual HTTP
request context):

```php
public function test_processes_incoming_webhook_update(): void
{
    $fakeUpdate = json_encode([
        'update_id' => 1,
        'message' => ['chat' => ['id' => 123], 'text' => '/start'],
    ]);

    file_put_contents('php://memory', $fakeUpdate); // or use a temp file path
    $this->bot->setInputSource('php://memory');

    // In practice, the bundled tests use a custom stream wrapper
    // (tests/Helpers/WebhookStreamWrapper.php) registered as a fake scheme,
    // so each test gets a fresh, isolated "input stream" rather than sharing
    // php://memory across tests. Mirror that pattern for your own webhook tests.

    $handled = false;
    $this->bot->processWebhook(function (array $update) use (&$handled) {
        $handled = true;
    });

    $this->assertTrue($handled);
}
```

For a robust setup, register your own stream wrapper (following
`tests/Helpers/WebhookStreamWrapper.php`'s pattern) rather than relying on
`php://memory`, which is a single global buffer shared process-wide and
can leak state between tests if you're not careful to reset it.

## Testing `CommandHandler`-based bots

Since `CommandHandler::handleUpdate()` takes a plain update array, you can
test command routing without any webhook machinery at all:

```php
public function test_start_command_sends_welcome(): void
{
    $this->bot->commands()->register('start', function ($bot, $chatId, $args) {
        $bot->messages()->send(['chat_id' => $chatId, 'text' => 'Welcome!']);
    });

    $this->mockClient->setResponse(['message_id' => 1]);

    $handled = $this->bot->commands()->handleUpdate([
        'message' => ['chat' => ['id' => 123], 'text' => '/start'],
    ]);

    $this->assertTrue($handled);
    $this->assertSame('Welcome!', $this->mockClient->getLastRequest()['params']['text']);
}
```

## Running this framework's own test suite

If you're contributing to the framework itself (rather than testing a bot
you built on top of it):

```bash
vendor/bin/phpunit                    # all tests
vendor/bin/phpunit tests/Unit/Api/    # a specific suite
vendor/bin/phpstan analyse            # static analysis (level 8 + strict rules)
vendor/bin/rector process --dry-run   # preview automated refactoring suggestions
```

Tests are organized under `tests/Unit/` (mocked HTTP, the majority of the
suite), `tests/Integration/`, `tests/Benchmark/` (bulk operation
performance), and `tests/EndToEnd/`, with shared fixtures in
`tests/Helpers/` (`MockHttpClient`, `MockTelegramResponse`,
`TestDataFactory`, `WebhookStreamWrapper`).

---

[← Previous: HTTP Clients](20-http-clients.md) | [Documentation Home](README.md) | [Next: Cookbook →](22-cookbook.md)
