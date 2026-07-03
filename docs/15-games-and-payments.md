[← Documentation Home](README.md)

# Games & Payments

`GamesService` and `PaymentsService` (`src/Api/Methods/GamesService.php`,
`src/Api/Methods/PaymentsService.php`), reached via `$bot->games()` and
`$bot->payments()`, cover Telegram's HTML5 game platform and its
invoicing/payments API.

## Sending a game

Telegram games are HTML5 games registered with `@BotFather` under a
"short name." Once registered, send one into a chat with:

```php
$message = $bot->games()->sendGame([
    'chat_id' => $chatId,
    'game_short_name' => 'my_registered_game',
]);
```

This sends a message with a "Play" button that opens your game. It's
unrelated to `InlineService::createGame()` (see
[Polls, Inline Mode & Topics](13-polls-inline-topics.md)), which only
builds the inline-search-result payload shown when a user searches for a
game via `@yourbot query` — that method does not send a game message on
its own; `GamesService::sendGame()` is what actually posts one to a chat.

## Managing scores

```php
$bot->games()->setGameScore([
    'user_id' => $userId,
    'score' => 100,
    'chat_id' => $chatId,
    'message_id' => $gameMessageId,
]);

$highScores = $bot->games()->getGameHighScores([
    'user_id' => $userId,
    'chat_id' => $chatId,
    'message_id' => $gameMessageId,
]);
```

Both methods require **exactly one** of `chat_id` + `message_id`, or
`inline_message_id` — use the latter if the game message was sent via
inline mode rather than directly to a chat. Passing both, or neither, is
rejected by Telegram.

`setGameScore()` returns the updated `Message` object, or `true` if the
game message can't be edited (for example, if it was sent inline).

## Sending an invoice

```php
$bot->payments()->sendInvoice([
    'chat_id' => $chatId,
    'title' => 'Premium Subscription',
    'description' => 'One month of premium features',
    'payload' => 'premium_sub_' . $userId, // your own internal tracking string, not shown to the user
    'provider_token' => 'your-payment-provider-token',
    'currency' => 'USD',
    'prices' => [
        ['label' => 'Premium Subscription', 'amount' => 999], // amount is in the smallest currency unit — 999 = $9.99
    ],
]);
```

`payload` is yours to define — Telegram passes it back unchanged in the
`successful_payment` update so you can identify what was purchased without
a separate lookup.

### Telegram Stars payments

For in-app purchases paid with Telegram's own Stars currency (no external
payment provider needed), omit `provider_token` (or pass an empty string)
and use currency `XTR`:

```php
$bot->payments()->sendInvoice([
    'chat_id' => $chatId,
    'title' => 'Unlock Feature',
    'description' => 'Unlocks the pro feature permanently',
    'payload' => 'unlock_feature_' . $userId,
    'provider_token' => '',
    'currency' => 'XTR',
    'prices' => [['label' => 'Unlock Feature', 'amount' => 50]], // 50 Telegram Stars
]);
```

### Handling the completed payment

Telegram sends a `successful_payment` field inside a `message` update once
the user pays — there's no dedicated service method for this since it's
just data you read off the incoming update, not an action you call:

```php
$bot->processWebhook(function (array $update) use ($bot) {
    $payment = $update['message']['successful_payment'] ?? null;

    if ($payment !== null) {
        $payload = $payment['invoice_payload']; // your own tracking string from sendInvoice()
        // ... grant whatever $payload represents ...
    }
});
```

Telegram also requires bots to answer a `pre_checkout_query` (sent right
before payment is finalized) within 10 seconds via `answerPreCheckoutQuery`.
**This method has no `ApiMethod` enum case or service wrapper yet** — it's
a genuine gap in current API coverage, not an oversight in this
documentation. Since `ApiService::call()` only accepts an `ApiMethod` enum
value (see [The Bot Facade](05-the-bot-facade.md)), calling it today means
either:

- Adding the enum case and a `PaymentsService::answerPreCheckoutQuery()`
  method yourself, following the pattern in CLAUDE.md's "How to Extend
  Safely" section — this is the recommended path since it keeps the call
  consistent with the rest of the framework (logging, exception handling,
  bulk-manager access all come for free), or
- Calling the HTTP client directly, bypassing `ApiService`:

  ```php
  $config = $bot->api()->getConfig();
  $bot->api()->getBulkManager(); // (unrelated; shown only to illustrate ApiService's public surface)

  // Manual one-off call, skipping ApiService's logging/exception wrapping:
  $httpClient = \AhmCho\Telegram\Client\HttpClientFactory::create($config);
  $httpClient->request(
      \AhmCho\Telegram\Enums\HttpMethod::POST,
      $config->getFullApiUrl() . 'answerPreCheckoutQuery',
      ['pre_checkout_query_id' => $update['pre_checkout_query']['id'], 'ok' => true]
  );
  ```

The first option is strongly preferred for anything you'll call more than
once.

## Method reference

| Method | Telegram API method | Notes |
|---|---|---|
| `GamesService::sendGame(array $params): array` | `sendGame` | Sends an actual game message |
| `GamesService::setGameScore(array $params): mixed` | `setGameScore` | Returns `Message` or `true` |
| `GamesService::getGameHighScores(array $params): array` | `getGameHighScores` | Array of `GameHighScore` |
| `PaymentsService::sendInvoice(array $params): array` | `sendInvoice` | Use currency `XTR` + empty `provider_token` for Telegram Stars |

---

[← Previous: Invite Links](14-invite-links.md) | [Documentation Home](README.md) | [Next: Bulk Operations & Broadcasting →](16-bulk-operations.md)
