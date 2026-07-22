# Extending the Codebase

[← Back to CLAUDE.md](../../CLAUDE.md)

## Where Logic Lives

**Service Layer** → Domain-specific business logic

- MessageService: Text formatting, auto-escaping
- MediaService: File handling, caption auto-escaping
- ChatService: Chat administration
- WebhookService: Webhook management

**API Layer** → Pure HTTP orchestration, no business logic

**Facade Layer** → Entry point; `TelegramBot` wires all services together

## What to Avoid

- Instantiating services directly (use TelegramBot)
- Bypassing ApiService in new service classes
- Calling `$bot->api()->call()` directly when a service method exists
- Hardcoding tokens or configuration
- Mixing HTTP concerns into service classes

## How to Extend Safely

**Adding New API Methods:**

1. Add enum value to `ApiMethod` (`src/Enums/ApiMethod.php`)
2. Add method to appropriate service class using `$this->apiService->call(ApiMethod::YOUR_METHOD, $params)`
3. Add accessor to `TelegramBot` if service is new

**Adding New Services:**

```php
<?php
declare(strict_types=1);

namespace AhmCho\Telegram\Api\Methods;

use AhmCho\Telegram\Api\ApiService;

class YourDomainService
{
    public function __construct(
        private readonly ApiService $apiService
    ) {}

    public function doSomething(array $params): array
    {
        return $this->apiService->call(ApiMethod::YOUR_METHOD, $params);
    }
}
```

Add the service to `TelegramBot`'s constructor and add an accessor method.

**Using Custom HTTP Client:**

```php
$httpClient = new CustomHttpClient();
$config = new BotConfig(token: 'your_token');
$bot = new TelegramBot(null, $config, $httpClient);
```

## Code Conventions

| Type       | Convention           |
| ---------- | --------------------- |
| Classes    | PascalCase           |
| Methods    | camelCase             |
| Properties | camelCase             |
| Constants  | SCREAMING_SNAKE_CASE  |

- One class per file, filename matches class name
- Namespace matches directory structure under `AhmCho\Telegram`
- `declare(strict_types=1);` at top of every file
- Public methods must have type annotations; complex arrays use `@param array<key, type>`
