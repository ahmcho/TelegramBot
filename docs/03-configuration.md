[← Documentation Home](README.md)

# Configuration

Configuration in this framework revolves around one object: `BotConfig`
(`src/Config/BotConfig.php`). It is **immutable** — every setting is a
`readonly` constructor property, and every "change" method (`with*()`)
returns a brand-new `BotConfig` instance rather than mutating the existing
one. This means a `BotConfig` you hand to one part of your app can never be
silently changed by another part.

## The simplest case: just a token

If you only need a token, you don't need to touch `BotConfig` directly —
`TelegramBot`'s constructor builds one for you:

```php
use AhmCho\Telegram\Bot\TelegramBot;

$bot = new TelegramBot('123456789:AA...'); // token passed directly
// or, with no argument, reads TELEGRAM_BOT_TOKEN from .env / the environment
$bot = new TelegramBot();
```

## Building a `BotConfig` explicitly

Every option and its actual default value (`src/Config/BotConfig.php:14-24`):

```php
use AhmCho\Telegram\Config\BotConfig;

$config = new BotConfig(
    token: 'your-bot-token',
    apiUrl: 'https://api.telegram.org/', // default; override for a local Bot API server
    timeout: 30,                          // seconds, per-request cURL/stream timeout
    throwExceptions: true,                // API errors throw; see docs/18-error-handling.md
    verifySsl: true,                      // disable only for local dev against self-signed certs
    loggingEnabled: true,                 // set false to disable logging entirely
    logFilePath: 'bot.log',
    logLevel: 'INFO',                     // DEBUG, INFO, NOTICE, WARNING, ERROR, CRITICAL, ALERT, EMERGENCY
    logMaxBytes: 0,                       // 0 = no rotation; see docs/19-logging.md
    logTimezone: 'UTC'                    // IANA timezone name for log timestamps
);

$bot = new TelegramBot(null, $config);
```

## Changing settings: the `with*()` mutators

Because `BotConfig` is immutable, "changing" a setting means creating a new
config from an old one:

```php
$devConfig = $config
    ->withVerifySsl(false)      // only for local dev against self-signed certs
    ->withTimeout(60)
    ->withLogLevel('DEBUG');

$bot = new TelegramBot(null, $devConfig);
```

Every field has a matching mutator: `withVerifySsl()`, `withTimeout()`,
`withThrowExceptions()`, `withLoggingEnabled()`, `withLogFilePath()`,
`withLogLevel()`, `withLogMaxBytes()`, `withLogTimezone()`. Each one
constructs a full new `BotConfig`, copying every other field forward
unchanged (`src/Config/BotConfig.php:113-189`).

> **Why immutable?** If `BotConfig` were mutable, a library or middleware
> deep in your call stack could flip `verifySsl` to `false` for one request
> and it would silently stay off for every request afterward, since PHP
> objects are passed by handle. Immutability makes that class of bug
> structurally impossible.

## `.env` files and `EnvLoader`

`EnvLoader` (`src/Config/EnvLoader.php`) is a small, dependency-free `.env`
parser. `TelegramBot`'s constructor calls it automatically — you never have
to invoke it yourself for the common case.

**Where it looks for `.env`** (`EnvLoader::findEnvFile()`,
`src/Config/EnvLoader.php:102-117`), in order:

1. `getcwd() . '/.env'` — your current working directory
2. One directory above `src/Config/` (i.e. the framework's own root, if
   you're running this framework standalone)
3. `__DIR__ . '/../../.env'` relative to `EnvLoader.php` itself

**What it supports:**

```dotenv
# Comments are skipped
TELEGRAM_BOT_TOKEN=123456:ABC-DEF

# Quoted values keep everything verbatim, including a literal #
API_NOTE="value with a # character"
API_NOTE_SINGLE='also # verbatim'

# Unquoted values strip a trailing inline comment
TIMEOUT=30 # seconds — this comment is stripped, TIMEOUT becomes "30"

# But a # with no preceding whitespace is NOT treated as a comment
COLOR=#FF0000
```

Parsed values are set into both `$_ENV` and via `putenv()`, so they're
visible to `getenv()` as well as `$_ENV` lookups elsewhere in your app.

**Reading values yourself**, if you need custom environment-driven config:

```php
use AhmCho\Telegram\Config\EnvLoader;

$loader = new EnvLoader();
$loader->load(); // or $loader->load('/absolute/path/to/.env')

$optional = $loader->get('SOME_OPTIONAL_VAR', 'default-value');
$required = $loader->require('SOME_REQUIRED_VAR'); // throws RuntimeException if missing
```

`EnvLoader::require()` is what powers `TelegramBot`'s "no token passed, read
from environment" path — if `TELEGRAM_BOT_TOKEN` is missing entirely, you
get a clear `RuntimeException: Required environment variable
'TELEGRAM_BOT_TOKEN' is not set.` rather than a confusing downstream
failure.

## Configuration reference: `.env` keys this framework understands

| Key | Used by | Purpose |
|---|---|---|
| `TELEGRAM_BOT_TOKEN` | `TelegramBot` constructor | Your bot token (required unless passed directly) |
| `TELEGRAM_VERIFY_SSL` | `public/webhook.php` example / your own bootstrap | Set `false` for local dev only |
| `TELEGRAM_WEBHOOK_SECRET` | `public/webhook.php` | Validates Telegram's secret token header — see [Webhooks](11-webhooks.md) |

## Custom HTTP clients and custom construction

For advanced use (e.g. injecting a mock client in tests, or a client with
custom proxy settings), the `TelegramBot` constructor accepts an optional
third argument:

```php
$bot = new TelegramBot(
    token: null,
    config: $config,
    httpClient: $myCustomHttpClient // must implement HttpClientInterface
);
```

See [HTTP Clients](20-http-clients.md) for what implementing
`HttpClientInterface` involves, and [Testing](21-testing.md) for how the
bundled `MockHttpClient` uses exactly this seam.

`BotFactory` (`src/Bot/BotFactory.php`) wraps a few common construction
patterns if you don't want to call `new TelegramBot(...)` directly:

```php
use AhmCho\Telegram\Bot\BotFactory;

$bot = BotFactory::create();                          // token from env
$bot = BotFactory::create('explicit-token');
$bot = BotFactory::createWithConfig($config);
$bot = BotFactory::createWithHttpClient($token, $client);
```

---

[← Previous: Installation](02-installation.md) | [Documentation Home](README.md) | [Next: Quickstart →](04-quickstart.md)
