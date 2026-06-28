<?php

declare(strict_types=1);

namespace AhmCho\Telegram\Tests\Unit\Examples;

use PHPUnit\Framework\TestCase;
use AhmCho\Telegram\Bot\TelegramBot;
use AhmCho\Telegram\Config\BotConfig;
use AhmCho\Telegram\Exception\ApiException;
use AhmCho\Telegram\Tests\Helpers\MockHttpClient;

/**
 * Tests for examples/retry-demo.php
 *
 * Verifies the retry mechanism demonstrated in the example:
 * - sendMessageWithRetry() succeeds on first attempt
 * - sendMessageWithRetry() retries on server errors (500)
 * - sendMessageWithRetry() does NOT retry on client errors (400)
 * - on_retry callback is called for each retry
 * - executeWithRetry() accepts any callable
 * - initial_delay_ms: 0 prevents tests from sleeping
 */
final class RetryExampleTest extends TestCase
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

    public function test_sendMessageWithRetry_succeeds_on_first_try(): void
    {
        $this->mockClient->setResponse(['message_id' => 1, 'chat' => ['id' => 123]]);

        $result = $this->bot->sendMessageWithRetry([
            'chat_id' => 123,
            'text' => 'Test message with automatic retry',
            'parse_mode' => 'MarkdownV2',
        ], [
            'max_retries' => 3,
            'initial_delay_ms' => 0,
        ]);

        $this->assertSame(1, $result['message_id']);
        $this->assertSame(1, $this->mockClient->getRequestCount());
    }

    public function test_sendMessageWithRetry_retries_on_server_error(): void
    {
        $this->mockClient->setException(new ApiException('Internal Server Error', 500, 500, []));
        $this->mockClient->setException(new ApiException('Internal Server Error', 500, 500, []));
        $this->mockClient->setResponse(['message_id' => 5, 'chat' => ['id' => 123]]);

        $result = $this->bot->sendMessageWithRetry([
            'chat_id' => 123,
            'text' => 'Retry test',
        ], [
            'max_retries' => 3,
            'initial_delay_ms' => 0,
        ]);

        $this->assertSame(5, $result['message_id']);
        $this->assertSame(3, $this->mockClient->getRequestCount());
    }

    public function test_sendMessageWithRetry_does_not_retry_on_400_error(): void
    {
        $this->mockClient->setException(new ApiException('Bad Request', 400, 400, []));

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('Bad Request');

        $this->bot->sendMessageWithRetry([
            'chat_id' => 123,
            'text' => 'This will fail',
        ], [
            'max_retries' => 3,
            'initial_delay_ms' => 0,
        ]);
    }

    public function test_sendMessageWithRetry_on_retry_callback_is_called(): void
    {
        $this->mockClient->setException(new ApiException('Server Error', 500, 500, []));
        $this->mockClient->setResponse(['message_id' => 10]);

        $retryAttempts = [];

        $this->bot->sendMessageWithRetry([
            'chat_id' => 123,
            'text' => 'Retry callback test',
        ], [
            'max_retries' => 3,
            'initial_delay_ms' => 0,
            'on_retry' => function ($attempt, $error, $delayMs) use (&$retryAttempts) {
                $retryAttempts[] = [
                    'attempt' => $attempt,
                    'error' => $error->getMessage(),
                    'delay' => $delayMs,
                ];
            },
        ]);

        $this->assertCount(1, $retryAttempts);
        $this->assertSame(1, $retryAttempts[0]['attempt']);
        $this->assertSame('Server Error', $retryAttempts[0]['error']);
    }

    public function test_executeWithRetry_accepts_any_callable(): void
    {
        $callCount = 0;

        $result = $this->bot->executeWithRetry(function () use (&$callCount) {
            $callCount++;
            return 'success_value';
        });

        $this->assertSame('success_value', $result);
        $this->assertSame(1, $callCount);
    }

    public function test_executeWithRetry_retries_callable_on_server_error(): void
    {
        $callCount = 0;

        $this->mockClient->setException(new ApiException('Service Unavailable', 503, 503, []));
        $this->mockClient->setResponse(['message_id' => 15]);

        $result = $this->bot->executeWithRetry(function () use (&$callCount) {
            $callCount++;
            return $this->bot->messages()->send(['chat_id' => 999, 'text' => 'Test']);
        }, [
            'max_retries' => 2,
            'initial_delay_ms' => 0,
        ]);

        $this->assertSame(15, $result['message_id']);
        $this->assertSame(2, $callCount);
    }

    public function test_sendMessageWithRetry_exhausts_retries_and_throws(): void
    {
        $this->mockClient->setException(new ApiException('Error', 500, 500, []));
        $this->mockClient->setException(new ApiException('Error', 500, 500, []));
        $this->mockClient->setException(new ApiException('Error', 500, 500, []));
        $this->mockClient->setException(new ApiException('Error', 500, 500, []));

        $this->expectException(ApiException::class);

        $this->bot->sendMessageWithRetry([
            'chat_id' => 123,
            'text' => 'Will fail all retries',
        ], [
            'max_retries' => 3,
            'initial_delay_ms' => 0,
        ]);
    }

    public function test_executeWithRetry_with_custom_options(): void
    {
        $this->mockClient->setResponse(['message_id' => 20]);

        $result = $this->bot->executeWithRetry(
            fn() => $this->bot->messages()->send(['chat_id' => 100, 'text' => 'Custom options test']),
            [
                'max_retries' => 5,
                'initial_delay_ms' => 0,
                'max_delay_ms' => 0,
            ]
        );

        $this->assertSame(20, $result['message_id']);
    }

    public function test_retry_does_not_apply_to_403_forbidden(): void
    {
        $this->mockClient->setException(new ApiException('Forbidden', 403, 403, []));

        $this->expectException(ApiException::class);

        $this->bot->sendMessageWithRetry([
            'chat_id' => 123,
            'text' => 'Forbidden message',
        ], [
            'max_retries' => 3,
            'initial_delay_ms' => 0,
        ]);

        $this->assertSame(1, $this->mockClient->getRequestCount(), 'Should not retry on 403');
    }

    public function test_sendBulkWithRetry_succeeds(): void
    {
        $this->mockClient->setResponse(['message_id' => 30]);
        $this->mockClient->setResponse(['message_id' => 31]);

        $result = $this->bot->sendBulkWithRetry(
            [
                ['chat_id' => 100, 'text' => 'Bulk 1'],
                ['chat_id' => 200, 'text' => 'Bulk 2'],
            ],
            [],
            ['initial_delay_ms' => 0]
        );

        $this->assertSame(2, $result->total);
        $this->assertSame(2, $result->successful);
    }
}
