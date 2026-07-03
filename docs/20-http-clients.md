[← Documentation Home](README.md)

# HTTP Clients

Every HTTP request this framework makes — whether triggered by a service
method, a bulk operation, or the retry layer — ultimately goes through an
object implementing `HttpClientInterface` (`src/Client/HttpClientInterface.php`).
This page explains the two built-in implementations, how the framework
picks between them, and what's involved in writing your own.

## The two built-in clients

```
HttpClientInterface
├── CurlHttpClient     — default, requires the `curl` extension, supports true parallel requests
└── StreamHttpClient   — fallback, requires only `openssl`, executes "parallel" requests serially
```

`HttpClientFactory::create()` (`src/Client/HttpClientFactory.php`) picks
`CurlHttpClient` if `curl_init()` is available, otherwise
`StreamHttpClient` if `openssl` is available, otherwise throws
`HttpClientException` — there is no HTTP transport with neither extension.
`TelegramBot`'s constructor calls this automatically unless you pass your
own client (see [Configuration](03-configuration.md)).

You can force a specific client rather than letting the factory choose:

```php
use AhmCho\Telegram\Client\HttpClientFactory;

$curlClient = HttpClientFactory::createCurl($config);     // throws if curl isn't loaded
$streamClient = HttpClientFactory::createStream($config); // throws if openssl isn't loaded

$bot = new TelegramBot(null, $config, $streamClient);
```

## What each client actually does

Both implement the same interface:

```php
interface HttpClientInterface
{
    public function request(HttpMethod $method, string $url, array $params = []): mixed;
    public function requestMulti(HttpMethod $method, string $url, array $requestsArray, array $options = []): array;
    public function getLastHttpCode(): int;
    public static function isAvailable(): bool;
}
```

- **`CurlHttpClient::requestMulti()`** uses `curl_multi_exec` to run
  requests genuinely concurrently, up to `max_concurrent` at a time (see
  [Bulk Operations & Broadcasting](16-bulk-operations.md)).
- **`StreamHttpClient::requestMulti()`** has no concurrency primitive
  available via PHP streams, so it falls back to calling `request()` in a
  serial loop, honoring `delay_ms` between requests and logging a
  one-time warning ("falling back to serial execution") the first time
  it's invoked. Correctness is identical either way — only wall-clock
  time differs. If throughput matters for your bulk sending, prefer
  `CurlHttpClient` (which is what you'll get by default on any server with
  the `curl` extension, which is the overwhelming majority).

## File uploads and multipart requests

When you send a `CURLFile` (see [Media & Files](09-media-and-files.md)),
the request body has to become `multipart/form-data` instead of a plain
JSON body — a file's binary contents can't be embedded in JSON. Both
clients detect this the same way, via a shared
`MultipartRequestTrait::hasFileUpload()`
(`src/Client/Traits/MultipartRequestTrait.php`): if any top-level
parameter value is a `\CURLFile` instance, the request is built as
multipart; otherwise it's sent as `application/json` with `json_encode()`'d
params.

- **`CurlHttpClient`** hands the raw params array (including any
  `CURLFile` objects) directly to `CURLOPT_POSTFIELDS` — PHP's `curl`
  extension natively understands `CURLFile` values in this context and
  builds the multipart body itself.
- **`StreamHttpClient`** has no equivalent native support in PHP's stream
  wrappers, so `MultipartRequestTrait::buildMultipartBody()` constructs
  the multipart/form-data body by hand: a random boundary, one part per
  field (arrays are JSON-encoded, booleans become `'1'`/`''`, `null`
  values are skipped), and one part per `CURLFile` with its actual file
  contents read via `file_get_contents()` and the correct
  `Content-Disposition`/`Content-Type` headers.

**This is why `sendMediaGroup()` requires care with nested file uploads**
(see [Media & Files](09-media-and-files.md)): `hasFileUpload()` only
inspects **top-level** parameter values. A `CURLFile` nested inside
`params['media'][0]['media']` would not be detected there — which is
exactly why `MediaService::sendMediaGroup()` hoists any `CURLFile` found
inside a media item into a top-level `media_attach_N` field before the
request ever reaches the HTTP client layer. If you ever build your own
service method that accepts nested arrays potentially containing files,
follow that same pattern: extract files to top-level params yourself
before calling `ApiService::call()`.

## Writing your own HTTP client

Implement `HttpClientInterface` and pass an instance to `TelegramBot`'s
constructor. This is the seam [Testing](21-testing.md) uses for
`MockHttpClient`, and it's the same seam you'd use for, say, a client with
custom proxy configuration, request/response middleware, or a different
retry strategy at the transport level (distinct from the
application-level retry covered in [Retry & Resilience](17-retry-and-resilience.md)):

```php
use AhmCho\Telegram\Client\HttpClientInterface;
use AhmCho\Telegram\Enums\HttpMethod;

class LoggingProxyHttpClient implements HttpClientInterface
{
    public function __construct(private readonly HttpClientInterface $inner) {}

    public function request(HttpMethod $method, string $url, array $params = []): mixed
    {
        error_log("Calling {$url}");
        return $this->inner->request($method, $url, $params);
    }

    public function requestMulti(HttpMethod $method, string $url, array $requestsArray, array $options = []): array
    {
        return $this->inner->requestMulti($method, $url, $requestsArray, $options);
    }

    public function getLastHttpCode(): int
    {
        return $this->inner->getLastHttpCode();
    }

    public static function isAvailable(): bool
    {
        return true;
    }
}
```

```php
$bot = new TelegramBot(null, $config, new LoggingProxyHttpClient(
    \AhmCho\Telegram\Client\HttpClientFactory::create($config)
));
```

Every service, the bulk manager, and the retry layer call through
whatever `HttpClientInterface` you provide — there's no special-casing of
the two built-in clients anywhere else in the framework.

## Response parsing

Both clients share `ResponseParserTrait` (`src/Client/Traits/ResponseParserTrait.php`)
for turning Telegram's raw JSON response into either a decoded value (on
`"ok": true`) or the appropriate exception (`ApiException` for
`"ok": false`, `HttpClientException` for invalid/non-object JSON) — see
[Error Handling](18-error-handling.md) for what each exception type means
and how to catch them.

---

[← Previous: Logging](19-logging.md) | [Documentation Home](README.md) | [Next: Testing →](21-testing.md)
